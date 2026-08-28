---
paths:
  - app/Providers/AppServiceProvider.php
---

# Providers

## Use Relation::morphMap(), never enforceMorphMap(), unless all morphs are audited
`Relation::enforceMorphMap()` requires every polymorphic relation app-wide to resolve via the map, or it throws ClassMorphViolationException. This codebase has pre-existing untyped morph relations (e.g. the Likeable trait on Article/Player/Team/Game). Use `Relation::morphMap()` (non-enforcing) to register aliases for new relations (e.g. LeagueStanding's `competitor`) without breaking existing ones.
