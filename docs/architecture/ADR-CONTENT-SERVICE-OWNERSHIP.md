# ADR — durable policy, discussion and social-profile ownership

Date: 2026-10-22. Status: implemented, scoped verification recorded; not whole-project architecture approval.

## Decision

The companion owns the durable business rules and submission handlers formerly in the theme. The theme keeps presentation bridges, the existing shared admin entry and its social-profile editing markup. No new top-level menu or second option store is introduced.

| Owner | Persistent contract / hook |
|---|---|
| `Content/Product_Policy_Store` | Existing `zarincode_product_policies`, `_zc_product_policies`, `_zc_policy_mode`, `_zc_product_changelog` keys |
| `Admin/Product_Policy_Admin` | Native product metabox, nonce/capability-protected shared-policy save, companion-owned editor JS |
| `Content/Social_Profile_Store` | Existing `zarincode_custom_socials`, bounded URL/SVG sanitization, stable row IDs |
| `Content/Discussion_Service` | Native WordPress comment parent/post binding, bounded reply depth and Woo verified-buyer validation |
| Theme `Core/Product_Policies`, `Core/Social_Manager`, `Core/Discussion` | Presentation/delegation; existing settings navigation and public rendering |

The three services have explicit feature-registry flags. Disabled features are not booted in a fresh request; theme policy/profile display bridges also respect disabled flags. Data is not deleted on disable or theme switch. WordPress's own threading preference is not overwritten. CSS cache invalidation is limited to feature records declaring `elementor-css`.

## Migration and compatibility

No bulk data rewrite or metadata renaming occurs. Policy writes preserve unknown root keys and unknown FAQ-row keys matched by stable ID. Profile writes preserve unknown row keys matched by ID. Current writes add version 1 schema markers. Unknown/future schema versions reject the submission rather than downgrade it, including malformed profile IDs and null version markers.

Partial policy submissions retain fields absent from the request. To distinguish intentionally removing the final row from an omitted field, actual admin forms include an empty hidden list sentinel before row controls. PHP replaces that scalar with the submitted nested row array when rows exist. An explicit empty sentinel clears the list. Both production scripts guard repeated evaluation, preserving one handler per document.

Public new actions: `zarincode_policy_write_rejected`, `zarincode_product_policies_saved`, `zarincode_social_profiles_saved`. Successful option-save actions fire only after the write succeeds or the stored value already equals the normalized value. Product-meta save uses native WordPress metadata APIs; this does not introduce a cross-meta SQL transaction or certify database-failure recovery.

Existing user data, approved visual design and passive translation configuration remain. WPML is explicitly excluded from current acceptance; gettext and direction support are not removed.

## Evidence and limits

See `docs/audits/2026-10-22-content-migration/`: real WordPress/Woo PHPUnit, fresh-request disabled-hook checks, browser interaction with PHP-rendered admin surfaces, HPOS/classic CRUD and real guest COD checkout. Unit runs use an official fallback theme, verifying companion independence rather than relying on a live Zarincode theme.

No claim of full legacy-service migration, complete security review, visual equivalence, every official widget's editor parity, real Polylang compatibility or final Skill 3 acceptance follows from this ADR. Remaining gates are explicit in `docs/governance/release-gates.json`.
