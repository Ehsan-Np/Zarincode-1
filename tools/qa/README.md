# QA helpers (rebuild the test site from scratch)

The WordPress test install never lives in the workspace — it is built under
`/tmp/qa3` on demand and thrown away at the end of a cycle.

```bash
bash tools/qa/setup_env.sh     # php 8.4 + playwright chromium (sudo)
bash tools/qa/build_qa.sh      # WP + sqlite + Elementor + WooCommerce + demo seed
php -S 0.0.0.0:8897 -t /tmp/qa3 /tmp/qa3/router.php   # start the site
python3 tools/qa/shot.py http://127.0.0.1:8897/ /tmp/shots/home.png 1440 [full]
```

`shot.py` also prints console errors and the header geometry (pill / nav /
brand / account / CTA / hamburger sizes), which is how the header regressions
of 4.16.0 were caught.
