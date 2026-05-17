# Fight Counter Reset Fix - Session-Based Approach

## Problem
When the admin reset the fight counter, it was showing "latest number" instead of resetting to 1. This happened because:
1. Old approach deleted active fights but preserved completed ones (status='done')
2. The next fight calculation still picked the highest fight_number from ANY remaining fight
3. If completed fights had higher numbers, those numbers would be used

Example: If fights 1-50 were completed yesterday, and we reset today:
- Old approach: deleted today's fights, kept completed 1-50
- Counter calculation: max(1-50) + 1 = 51
- Result: Next fight shows as #51 instead of #1 ❌

## Solution: Session-Based Fight Tracking
Instead of deleting fights, use a `session_date` field to track which session/day fights belong to:

### Changes Made:

#### 1. **Database Migration**
File: `database/migrations/2026_05_18_000000_add_session_date_to_fights_table.php`

Added:
- `session_date` (nullable date field) - null means current active session
- Index on `session_date` for efficient querying

When reset: All fights WITHOUT a session_date get marked with today's date (archiving them)
New fights: Created with NULL session_date (current session)

#### 2. **Backend API Updates**

**File: `app/Http/Controllers/Api/FightController.php`**

- **`current()` method**: Filter by `whereNull('session_date')` - only current session
- **`store()` method**: Check for active fights using `whereNull('session_date')`
- **`history()` method**: Return only `whereNull('session_date')` fights
- **`reset()` method**: Instead of deleting, mark all NULL session_date fights with today's date

#### 3. **Android Frontend Update**

**File: `app/src/main/java/com/yego/sabongbettingsystem/ui/admin/AdminCreateFightScreen.kt`**

- Updated comment to explain session-based filtering
- Next fight calculation now only counts fights in current session (backend handles filtering)

## How It Works (Example Scenario)

### Day 1
- Create fights #1, #2, #3
- All have `session_date = null`
- Status: pending/open/closed

### Day 2 - Reset Button Pressed
```
UPDATE fights 
SET session_date = '2026-05-18' 
WHERE session_date IS NULL
```

Result:
- Fights #1-3 now have `session_date = '2026-05-18'` (archived to session)
- Queries with `whereNull('session_date')` no longer find them

### Day 2 - Create New Fight
- API queries: `WHERE session_date IS NULL`
- No fights found, so `max() = 0`
- Next fight = 0 + 1 = #1 ✓
- New fight #1 created with `session_date = null`

### Viewing Historical Data
- Admin can query with session_date filters to see previous day's fights
- Day 1 fights: `WHERE session_date = '2026-05-18'`
- Current fights: `WHERE session_date IS NULL`

## Benefits
✅ No fight deletion - complete historical preservation
✅ Clean counter reset to #1 each session
✅ Multiple fights can share the same number across different days (organized by session)
✅ Scalable - can view any session's fights
✅ No duplicate conflicts - fights from different days are isolated

## Verification Checklist
- [ ] Migration ran successfully
- [ ] Android app shows #1 after reset
- [ ] Fights increment normally (#1, #2, #3...)
- [ ] Previous session fights are preserved
- [ ] API queries return only current session fights
