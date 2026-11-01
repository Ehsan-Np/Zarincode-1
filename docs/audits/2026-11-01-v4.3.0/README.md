# 4.3.0 verification evidence (2026-11-01)

- 43-project-v2.png — project single, product-pattern desktop (breadcrumb, hero+KPI, tabs, sticky sidebar, dev card)
- 43-project-full.png — full page incl. compact share studio (bale icon visible)
- 43-project-mobile.png — project single at 390px (grid collapses, launcher OK)
- 43-product-neighbor.png — product page unchanged (no regression)
- 43-chat-panel.png — restyled chat panel with sound bell toggle
- 43-chat-settings.png — settings incl. new «اعلام‌ها و اعلان‌ها» section
- 43-inbox-list.png — stats cards (3/3/0/0), search, tabs with counts, quick actions
- 43-inbox-detail.png — two-column detail (fields + facts + status/quick actions)

Method: chromium headless (chromium --headless=new --virtual-time-budget 9000-12000) against /tmp/wpenv (php -S :8090), local-only autologin mu-plugin for admin screens (removed from deliverables — lives outside repo trees in /tmp).
