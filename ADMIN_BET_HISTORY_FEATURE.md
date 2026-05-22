# Admin Bet History Feature Implementation

## Overview
Added a new feature to the Admin Cash-In mode that allows admins to view and remove their own betting history. This includes a dedicated tab with a list of all bets placed by the admin, displaying relevant information and allowing deletion of individual bets.

## Changes Made

### 1. Backend - Laravel API Endpoints

#### File: `app/Http/Controllers/Api/BetController.php`
Added two new methods:

**`adminHistory(Request $request)`**
- Endpoint: `GET /api/admin/bet/history`
- Returns all bets placed by the authenticated admin
- Returns formatted bet history with:
  - Bet ID, Reference, Fight Number
  - Side (MERON/WALA), Amount, Created Date/Time
  - Winner, Win Status, Payout Status
  - Net Payout Amount

**`adminDestroyBet(Request $request, $betId)`**
- Endpoint: `DELETE /api/admin/bet/{id}`
- Deletes a specific bet by ID
- Only allows deletion of bets placed by the authenticated admin
- Logs deletion to audit log
- Returns success message

#### File: `routes/api.php`
Added routes under the admin middleware:
```php
// Admin bet history management
Route::get('/admin/bet/history', [BetController::class, 'adminHistory']);
Route::delete('/admin/bet/{id}', [BetController::class, 'adminDestroyBet']);
```

### 2. Android - API Service

#### File: `app/src/main/java/com/yego/sabongbettingsystem/data/api/ApiServices.kt`
Added two new API methods:

**`getAdminBetHistory(token: String): Response<BetHistoryResponse>`**
- GET request to `/admin/bet/history`
- Returns history of all bets placed by the admin

**`deleteAdminBet(token: String, id: Int): Response<MessageResponse>`**
- DELETE request to `/admin/bet/{id}`
- Deletes a specific bet

### 3. Android - ViewModel

#### File: `viewmodel/CashInViewModel.kt`
Enhanced the CashInViewModel with admin bet management:

**New State Flow:**
- `_adminBetHistory`: StateFlow<List<BetResponse>> - Stores list of admin bets
- `adminBetHistory`: StateFlow<List<BetResponse>> - Public access to bet history

**New Methods:**

`loadAdminBetHistory(context: Context)`
- Loads all bets placed by the admin
- Handles API response and error states
- Updates the adminBetHistory state flow

`deleteAdminBet(context: Context, betId: Int)`
- Deletes a specific bet by ID
- Automatically refreshes the history after deletion
- Handles errors appropriately

### 4. Android - UI Component

#### File: `app/src/main/java/com/yego/sabongbettingsystem/ui/admin/AdminCashInScreen.kt`

**Enhanced Main Screen:**
- Added tab-based navigation with two tabs:
  1. "Place Bet" - Original betting interface
  2. "Bet History" - New history view

- Added new state variables:
  - `selectedTabIndex`: Tracks which tab is active
  - `adminBetHistory`: Displays loaded bet history

- Updated LaunchedEffect hooks:
  - Loads admin bet history on screen initialization
  - Reloads bet history when a new bet is placed

**New Composable: `AdminBetHistoryView`**
- Displays a list of all admin bets
- Features for each bet card:
  - Reference Number and Date
  - Delete Button (Remove)
  - Side (MERON/WALA) with color coding
  - Bet Amount
  - Fight Number
  - Bet Status (pending/won/lost)
  - Net Payout (if won)

- Delete Functionality:
  - Shows confirmation dialog before deletion
  - Calls `onDeleteBet` callback on confirmation
  - Automatically refreshes list after deletion

- Loading States:
  - Shows progress indicator while loading
  - Displays "No bets found" message if empty

## UI/UX Features

### Bet History Display
Each bet is displayed in a card with:
- **Top Row**: Reference number, date, and delete button
- **Details Row**: Side, Amount, Fight Number
- **Status Row**: Bet status and payout information (if won)

### Delete Confirmation
- Confirmation dialog appears before deletion
- User must confirm to proceed with deletion
- Provides cancel option

### Color Coding
- MERON side: Red (error color)
- WALA side: Blue (primary color)
- Won bets: Green (primary color)
- Lost bets: Red (error color)
- Pending: Grey (onSurfaceVariant color)

## Data Flow

1. Admin opens Cash-In mode
2. `loadAdminBetHistory()` is called automatically
3. List of bets is fetched from backend and displayed
4. Admin can:
   - Switch between "Place Bet" and "Bet History" tabs
   - View all their bets with details
   - Click "Remove" to delete a bet
   - Confirm deletion in the dialog
5. After deletion, history is automatically refreshed

## Security & Authorization

- All endpoints are protected with `auth:sanctum` middleware
- Admin-only endpoints use `role:admin` middleware
- Users can only delete their own bets (teller_id verification)
- All actions are logged in audit log

## API Response Format

### Get Admin Bet History Response
```json
{
  "data": [
    {
      "id": 1,
      "reference": "ABC-123456",
      "fight_number": "1",
      "side": "MERON",
      "amount": 1000.00,
      "created_at": "May 18, 2026 2:30 PM",
      "winner": "meron",
      "won": true,
      "status": "won",
      "net_payout": "2000.00"
    }
  ]
}
```

### Delete Response
```json
{
  "message": "Bet deleted successfully."
}
```

## Error Handling

- Network errors display appropriate messages
- Unauthorized deletions return 404
- All errors are displayed to the user
- Loading states provide user feedback

## Future Enhancements

Possible improvements for future versions:
1. Filter history by date range
2. Search by reference number
3. Export history to CSV
4. Undo deletion (soft delete with restore)
5. Batch operations (delete multiple bets)
6. Advanced analytics on betting history
7. Pagination for large history lists

## Testing Checklist

- [ ] Admin can view their betting history
- [ ] Each bet displays correct information
- [ ] Delete button appears on each bet
- [ ] Confirmation dialog works properly
- [ ] Successful deletion removes bet from list
- [ ] History automatically refreshes after deletion
- [ ] Error messages display correctly
- [ ] Loading states function properly
- [ ] Tab switching works smoothly
- [ ] Screen orientation changes are handled
