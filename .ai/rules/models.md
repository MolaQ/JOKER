---
paths:
  - 'app/Models/*.php'
---

# Models

## Use withPivotValue(), not wherePivotValue(), to auto-populate pivot columns on attach()
On belongsToMany relations, `wherePivot()`/`wherePivotValue()` only constrain SELECT queries. To have a pivot column both constrain queries AND auto-populate on attach()/sync(), use `withPivotValue($column, $value)`. See Team::seasonRoster().
