# Article rating ownership and canonical aggregation

Status: implemented in development; not a final release approval.

- The companion owns rating validation and persistence and registers them only
  when the `article_ratings` feature is enabled. It requires neither Zarincode's
  theme nor Elementor. The theme retains presentation and optional JSON-LD.
- The saved option `blog_article_ratings`, comment metadata `zc_article_rating`,
  and nonce action `zc_article_rating_{post_id}` are retained without migration
  or destructive rewriting. Uninstall continues to retain user data.
- Each approved ordinary comment has at most one effective vote. On historical
  duplicate metadata the earliest valid 1–5 row wins; invalid rows are ignored.
  Moderation changes are immediate. This does not assert one vote per person.
- WordPress public APIs validate nonces and insert metadata. A prepared `%i`/`%d`
  repository query performs the aggregate, scoped to the target post before
  grouping. No arbitrary SQL identifiers or query strings come from the request.
- Theme schema does not execute an aggregate query when optional rating schema
  is disabled (the existing default). No Google Article review-star eligibility
  is asserted.
- UI markup, typography, cards, layout and settings keys are unchanged. If the
  companion is absent the form no longer advertises a non-functional rating input.

Limits: this migration covers article ratings only. Product policy persistence,
account/business responsibilities and other legacy services still need their own
ownership review. Line coverage is not branch or whole-project coverage.
