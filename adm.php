<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flui POS - Dashboard</title>
    <link rel="stylesheet" href="adm.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <div class="app-layout">
        <aside class="sidebar">
            <div class="logo">
                <div class="logo-icon"><i class="fa-solid fa-cash-register"></i></div>
                <div>
                    <h3>Flui POS</h3>
                    <p>Admin Dashboard</p>
                </div>
            </div>
            <nav class="side-nav">
                <a href="#" class="active"><i class="fa-solid fa-table-cells-large"></i> Dashboard</a>
                <a href="#"><i class="fa-solid fa-receipt"></i> Transactions</a>
                <a href="#"><i class="fa-solid fa-cart-shopping"></i> Orders</a>
                <a href="#"><i class="fa-solid fa-users"></i> Staff</a>
                <a href="#"><i class="fa-solid fa-box"></i> Inventory</a>
                <a href="#"><i class="fa-solid fa-chart-line"></i> Reports</a>
            </nav>
            <div class="sidebar-footer">
                <a href="#"><i class="fa-solid fa-gear"></i> Settings</a>
                <div class="user-profile">
                    <img src="https://via.placeholder.com/35" alt="User">
                    <div>
                        <h4>Alex Chen</h4>
                        <p>Store Manager</p>
                    </div>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-header">
                <div class="header-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search data...">
                </div>
                <div class="header-actions">
                    <button class="icon-btn"><i class="fa-regular fa-bell"></i></button>
                    <button class="icon-btn"><i class="fa-regular fa-calendar"></i></button>
                    <div class="mobile-avatar">
                        <img src="https://via.placeholder.com/35" alt="User">
                    </div>
                </div>
            </header>

            <section class="content-body">
                <div class="welcome-row">
                    <h2>Dashboard Overview</h2>
                    <div class="action-buttons">
                        <button class="btn-secondary">Export CSV</button>
                        <button class="btn-primary">New Order</button>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="card-top">
                            <span class="card-icon green-bg"><i class="fa-solid fa-wallet"></i></span>
                            <span class="trend positive">↗ 12.5%</span>
                        </div>
                        <p>Total Revenue</p>
                        <h3>$42,500.00</h3>
                    </div>
                    <div class="stat-card">
                        <div class="card-top">
                            <span class="card-icon blue-bg"><i class="fa-solid fa-file-invoice"></i></span>
                            <span class="trend negative">↘ 2.4%</span>
                        </div>
                        <p>Transactions</p>
                        <h3>1,284</h3>
                    </div>
                    <div class="stat-card">
                        <div class="card-top">
                            <span class="card-icon orange-bg"><i class="fa-solid fa-tag"></i></span>
                            <span class="trend positive">↗ 5.1%</span>
                        </div>
                        <p>Avg Ticket</p>
                        <h3>$33.10</h3>
                    </div>
                    <div class="stat-card">
                        <div class="card-top">
                            <span class="card-icon purple-bg"><i class="fa-solid fa-user-group"></i></span>
                            <span class="trend neutral">0%</span>
                        </div>
                        <p>Staff Active</p>
                        <h3>12</h3>
                    </div>
                </div>

                <div class="orders-container">
                    <div class="orders-header">
                        <h3>Recent Orders</h3>
                        <a href="#">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ORDER ID</th>
                                    <th>CUSTOMER</th>
                                    <th>TIME</th>
                                    <th>AMOUNT</th>
                                    <th>STATUS</th>
                                    <th>ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>#ORD-7721</td>
                                    <td>James Smith</td>
                                    <td>10:45 AM</td>
                                    <td>$54.20</td>
                                    <td><span class="status completed">Completed</span></td>
                                    <td><i class="fa-solid fa-ellipsis-vertical"></i></td>
                                </tr>
                                </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>

        <nav class="mobile-nav">
            <a href="#" class="active"><i class="fa-solid fa-house"></i></a>
            <a href="#"><i class="fa-solid fa-clock-rotate-left"></i></a>
            <a href="#"><i class="fa-solid fa-box-archive"></i></a>
            <a href="#"><i class="fa-solid fa-ellipsis"></i></a>
        </nav>
    </div>

</body>
</html>