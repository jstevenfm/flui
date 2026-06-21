/*
 * qr-scanner.js — Escáner QR compartido para Flui POS
 * --------------------------------------------------------------
 * Módulo reutilizable que encapsula el hardware de cámara + decodificación
 * QR mediante la librería html5-qrcode (cargada vía CDN en la página).
 *
 * RESPONSABILIDAD del módulo (lo que maneja):
 *   - Inicializar Html5Qrcode sobre un contenedor
 *   - Iniciar/detener la cámara (facingMode 'environment' = cámara trasera)
 *   - Decodificar el QR y entregar el código vía callback
 *   - Input manual: leer el código escrito por el usuario y entregarlo
 *   - Errores de hardware (permiso denegado, sin cámara, etc.)
 *
 * RESPONSABILIDAD de la página (lo que NO maneja este módulo):
 *   - Qué hacer con el código escaneado (lo define onScanResult)
 *   - A qué API llamar (buscarOrdenQR, buscarOrdenAdmin, etc.)
 *   - Mostrar resultados, paneles de verificación, reclamar, etc.
 *
 * Dependencia: librería global `Html5Qrcode` (mebjas/html5-qrcode, CDN).
 *
 * Uso:
 *   const scanner = initQrScanner({
 *       containerId: 'qr-reader',
 *       manualInputId: 'codigo-manual',
 *       manualBtnId: 'btn-buscar-manual',
 *       onScanResult: function(code) { buscarOrden(code); },
 *       onError: function(msg) { mostrarError(msg); },
 *       facingMode: 'environment'   // opcional, default 'environment'
 *   });
 *   scanner.start();   // arranca la cámara
 *   scanner.stop();    // detiene y libera la cámara
 *   scanner.buscarPorCodigo('ABC123');  // dispara onScanResult manualmente
 */
function initQrScanner(config) {
    // --- Validación de configuración ---
    const containerId = config.containerId;
    const manualInputId = config.manualInputId;
    const manualBtnId = config.manualBtnId;
    const onScanResult = typeof config.onScanResult === 'function' ? config.onScanResult : function () {};
    const onError = typeof config.onError === 'function' ? config.onError : function () {};
    const facingMode = config.facingMode || 'environment';

    if (!containerId) {
        throw new Error('initQrScanner: falta containerId en la configuración.');
    }

    // --- Estado interno ---
    let html5Qr = null;        // instancia de Html5Qrcode
    let scanning = false;      // ¿cámara activa?
    let lastCode = null;       // último código escaneado (anti-duplicados)
    let lastScanAt = 0;        // timestamp del último escaneo exitoso
    const COOLDOWN_MS = 2500;  // evita disparar el mismo código varias veces seguidas

    // --- Verifica que la librería html5-qrcode esté cargada ---
    function libCargada() {
        return typeof window.Html5Qrcode === 'function';
    }

    // --- Construye la instancia perezosamente (al primer start) ---
    function asegurarInstancia() {
        if (html5Qr) return true;
        if (!libCargada()) {
            onError('La librería de escaneo QR no está cargada. Recarga la página.');
            return false;
        }
        const cont = document.getElementById(containerId);
        if (!cont) {
            onError('No se encontró el contenedor del escáner (#' + containerId + ').');
            return false;
        }
        try {
            html5Qr = new window.Html5Qrcode(containerId);
        } catch (e) {
            onError('No se pudo inicializar el escáner QR.');
            return false;
        }
        return true;
    }

    // --- Entrega el código al callback de la página con antispam ---
    function entregarCodigo(code) {
        if (!code) return;
        const ahora = Date.now();
        if (code === lastCode && (ahora - lastScanAt) < COOLDOWN_MS) return;
        lastCode = code;
        lastScanAt = ahora;
        onScanResult(code);
    }

    // --- Callback de éxito al leer un QR ---
    function onScanSuccess(decodedText) {
        // Mientras la cámara está activa, html5-qrcode llama a este callback
        // repetidamente con el mismo código. El antispam arriba filtra duplicados.
        entregarCodigo(decodedText);
    }

    // --- Callback de error de frame (no fatal — log silencioso) ---
    function onScanError(err) {
        // Errores de frame individuales son normales (QR aún no enfocado).
        // No los propagamos para no llenar la UI de errores.
        // (console.debug para depuración sin ruido)
        if (typeof console !== 'undefined' && console.debug) {
            console.debug('qr frame error:', err);
        }
    }

    // --- Iniciar la cámara ---
    async function start() {
        if (scanning) return; // idempotente: si ya está activo, no hacer nada
        if (!asegurarInstancia()) return;

        // Soporte de cámara: getUserMedia es el requisito mínimo
        if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
            onError('Tu navegador no soporta acceso a la cámara.');
            return;
        }

        try {
            const config = {
                fps: 10,
                qrbox: function (viewWidth, viewHeight) {
                    // Área de escaneo cuadrada del 70% del mínimo lado
                    const minSide = Math.min(viewWidth, viewHeight);
                    const side = Math.floor(minSide * 0.7);
                    return { width: side, height: side };
                },
                aspectRatio: 1.0,
                // Decodifica todos los formatos QR soportados (QR por defecto)
            };
            await html5Qr.start(
                { facingMode: facingMode },
                config,
                onScanSuccess,
                onScanError
            );
            scanning = true;
        } catch (err) {
            // Traducir los errores más comunes a mensajes amigables
            let msg = 'No se pudo iniciar la cámara.';
            if (err && err.name === 'NotAllowedError') {
                msg = 'Permiso de cámara denegado. Habilítalo en el navegador y reintenta.';
            } else if (err && (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError')) {
                msg = 'No se encontró ninguna cámara en este dispositivo.';
            } else if (err && (err.name === 'NotReadableError' || err.name === 'TrackStartError')) {
                msg = 'La cámara está en uso por otra aplicación. Ciérrala e reintenta.';
            } else if (err && err.name === 'OverconstrainedError') {
                msg = 'La cámara no soporta el modo solicitado.';
            } else if (err && err.message) {
                msg = err.message;
            }
            scanning = false;
            onError(msg);
        }
    }

    // --- Detener la cámara y liberar eltrack ---
    async function stop() {
        if (!scanning || !html5Qr) {
            // Aunque no haya cámara activa, igual reseteamos flags
            scanning = false;
            lastCode = null;
            return;
        }
        try {
            await html5Qr.stop();
        } catch (e) {
            // stop() lanza si ya estaba detenido — lo ignoramos
        }
        try {
            html5Qr.clear();
        } catch (e) {
            // clear() también puede lanzar — lo ignoramos
        }
        scanning = false;
        lastCode = null;
    }

    // --- Disparar búsqueda manual (input de texto o código por API) ---
    function buscarPorCodigo(code) {
        const valor = (code === undefined || code === null)
            ? ''
            : String(code).trim();
        if (!valor) {
            // Si no se pasó código, intentar leer del input manual configurado
            if (manualInputId) {
                const input = document.getElementById(manualInputId);
                if (input && input.value.trim()) {
                    entregarCodigo(input.value.trim());
                    return;
                }
            }
            onError('Ingresa un código válido.');
            return;
        }
        entregarCodigo(valor);
    }

    // --- Wiring del input manual (si se configuró) ---
    if (manualBtnId) {
        const btn = document.getElementById(manualBtnId);
        if (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                buscarPorCodigo(undefined); // lee del input manual
            });
        }
    }
    if (manualInputId) {
        const input = document.getElementById(manualInputId);
        if (input) {
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    buscarPorCodigo(undefined);
                }
            });
        }
    }

    // --- API pública del módulo ---
    return {
        start: start,
        stop: stop,
        buscarPorCodigo: buscarPorCodigo,
        //helpers de introspección (sin mutación) — útiles para la página
        estaActivo: function () { return scanning; }
    };
}

// Exportar para uso global (no-module, <script src>) — compatible con cajero.php y adm.php
if (typeof window !== 'undefined') {
    window.initQrScanner = initQrScanner;
}