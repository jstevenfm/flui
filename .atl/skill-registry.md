# Skill Registry

**Delegator use only.** Any agent that launches sub-agents reads this registry to resolve compact rules, then injects them directly into sub-agent prompts. Sub-agents do NOT read this registry or individual SKILL.md files.

See `_shared/skill-resolver.md` for the full resolution protocol.

## User Skills

| Trigger | Skill | Path |
|---------|-------|------|
| Creating, opening, or preparing PRs for review | branch-pr | /home/stevengarudaos/.config/opencode/skills/branch-pr/SKILL.md |
| PRs over 400 lines, stacked PRs, review slices | chained-pr | /home/stevengarudaos/.config/opencode/skills/chained-pr/SKILL.md |
| Writing guides, READMEs, RFCs, onboarding, architecture, or review-facing docs | cognitive-doc-design | /home/stevengarudaos/.config/opencode/skills/cognitive-doc-design/SKILL.md |
| PR feedback, issue replies, reviews, Slack messages, or GitHub comments | comment-writer | /home/stevengarudaos/.config/opencode/skills/comment-writer/SKILL.md |
| Go tests, go test coverage, Bubbletea teatest, golden files | go-testing | /home/stevengarudaos/.config/opencode/skills/go-testing/SKILL.md |
| Creating GitHub issues, bug reports, or feature requests | issue-creation | /home/stevengarudaos/.config/opencode/skills/issue-creation/SKILL.md |
| Judgment day, dual review, adversarial review, juzgar | judgment-day | /home/stevengarudaos/.config/opencode/skills/judgment-day/SKILL.md |
| New skills, agent instructions, documenting AI usage patterns | skill-creator | /home/stevengarudaos/.config/opencode/skills/skill-creator/SKILL.md |
| Implementation, commit splitting, chained PRs, keeping tests with code | work-unit-commits | /home/stevengarudaos/.config/opencode/skills/work-unit-commits/SKILL.md |

## Compact Rules

Pre-digested rules per skill. Delegators copy matching blocks into sub-agent prompts as `## Project Standards (auto-resolved)`.

### branch-pr
- Every PR MUST link an approved issue — no exceptions
- Every PR MUST have exactly one `type:*` label
- Branch names MUST match `^(feat|fix|chore|docs|style|refactor|perf|test|build|ci|revert)/[a-z0-9._-]+$`
- Use conventional commits: `type(scope): description`
- Blank PRs without issue linkage are blocked by GitHub Actions
- Run shellcheck on modified scripts before opening PR

### chained-pr
- Split PRs over 400 changed lines unless maintainer accepts `size:exception`
- Keep each PR reviewable in ≤60 minutes
- Use one deliverable work unit per PR; keep tests/docs with the unit they verify
- State start, end, prior dependencies, follow-up, and out-of-scope in every chained PR
- Every child PR must include a dependency diagram marking current PR with 📍
- Feature Branch Chain: create draft tracker PR; child #1 targets tracker branch, later children target parent
- Do not mix chain strategies after choosing one

### cognitive-doc-design
- Lead with the answer; put context after
- Start with happy path, then add details and edge cases
- Group related info into small sections; keep flat lists short
- Use headings, labels, callouts so readers know where they are
- Prefer tables, checklists, examples over prose to memorize
- Design docs so reviewers can verify intent without reconstructing the whole story

### comment-writer
- Start with the actionable point; don't recap the whole PR before feedback
- Sound like a thoughtful teammate, not a corporate bot
- Keep it short: 1–3 paragraphs or a tight bullet list
- Give the technical reason when asking for a change
- Comment on the highest-value issue, not every tiny preference
- Match thread language (Spanish → Rioplatense voseo: podés, tenés, fijate, dale)
- No em dashes — use commas, periods, or parentheses

### go-testing
- Prefer table-driven tests with `t.Run(tt.name, ...)`
- Test behavior and state transitions, not implementation trivia
- Use `t.TempDir()` for filesystem tests; never rely on real home
- Keep integration tests skippable with `testing.Short()`
- For Bubbletea, test `Model.Update()` directly; use `teatest` only for interactive flows
- Golden files: update only through repo's `-update` path; rerun without `-update` after

### issue-creation
- Every issue MUST use a template (Bug Report or Feature Request); blank issues are disabled
- Issues auto-get `status:needs-review`; maintainer MUST add `status:approved` before any PR
- Questions go to Discussions, not issues
- Fill ALL required fields; check pre-flight checkboxes before submit

### judgment-day
- Resolve project skills before launching agents; inject same `Project Standards` block into both judges
- Launch two blind judges in parallel with identical target and criteria; never review yourself
- Classify warnings as real only if normal use can trigger them; otherwise downgrade to INFO
- Ask before fixing Round 1 confirmed issues
- After any fix, re-launch both judges in parallel before commit/push
- Terminal states: `JUDGMENT: APPROVED` or `JUDGMENT: ESCALATED` only
- After 2 fix iterations with remaining issues, ask whether to continue

### skill-creator
- A skill is a runtime instruction contract for an LLM, not human docs
- Keep body concise: 180–450 tokens target, 700 recommended max, 1000 hard max
- Required frontmatter: `name`, `description` (quoted, one line, trigger-first, ≤250 chars), `license`
- Use `disable-model-invocation: true` for sub-agent-only skills
- References must point to local files relative to skill directory
- Do not add a `Keywords` section; keep trigger words in `description`

### work-unit-commits
- Commit by deliverable work unit, not by file type
- Keep tests with the code they verify in the same commit
- Keep docs with the user-visible change they explain
- Each commit should tell a story; reviewer understands why from diff + message
- Each commit should be a candidate chained PR when the change grows
- SDD workload guard: group commits into chained PR slices if forecast >400 lines

## Project Conventions

| File | Path | Notes |
|------|------|-------|
| AGENTS.md | /home/stevengarudaos/Documents/Projects/study-projects/flui/AGENTS.md | Index — references project context, plan, stack, bugs, and conventions |

Read the convention files listed above for project-specific patterns and rules. All referenced paths have been extracted — no need to read index files to discover more.