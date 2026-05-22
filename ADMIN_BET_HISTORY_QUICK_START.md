# Admin Bet History Feature - Quick Reference

## Feature Overview
Admins can now view and delete their betting history from the Cash-In mode in the mobile app.

## User Flow

```
Admin Opens App → Selects Cash-In Mode
                    ↓
            Two Tabs Appear:
            1. "Place Bet" (original)
            2. "Bet History" (new)
                    ↓
            Click "Bet History" tab
                    ↓
            View list of all your bets
            Each bet shows:
            - Reference #, Date
            - Side (MERON/WALA)
            - Amount
            - Fight #
            - Status
            - Payout (if won)
                    ↓
            Click "Remove" button on any bet
                    ↓
            Confirmation dialog appears
                    ↓
            Click "Delete" or "Cancel"
                    ↓
            If deleted, list refreshes automatically
```

## Technical Implementation

### Backend
- **Added to**: `BetController.php`
  - `adminHistory()` - Returns admin's bet history
  - `adminDestroyBet($id)` - Deletes a specific bet

- **Routes**: `routes/api.php`
  - `GET /api/admin/bet/history`
  - `DELETE /api/admin/bet/{id}`

### Mobile
- **API Service**: `ApiServices.kt`
  - `getAdminBetHistory()` - GET request
  - `deleteAdminBet(id)` - DELETE request

- **ViewModel**: `CashInViewModel.kt`
  - `loadAdminBetHistory()` - Fetch history
  - `deleteAdminBet(id)` - Delete and refresh

- **UI**: `AdminCashInScreen.kt`
  - `AdminBetHistoryView()` - Bet list display
  - Tab-based navigation
  - Delete confirmation dialog

## API Usage

### Get Admin Bet History
```
GET /api/admin/bet/history
Authorization: Bearer {token}

Response:
{
  "data": [
    {
      "id": 1,
      "reference": "ABC-123456",
      "fight_number": "1",
      "side": "MERON",
      "amount": 1000.00,
      "created_at": "May 22, 2026 2:30 PM",
      "winner": "meron",
      "won": true,
      "status": "won",
      "net_payout": "2000.00"
    }
  ]
}
```

### Delete Admin Bet
```
DELETE /api/admin/bet/{id}
Authorization: Bearer {token}

Response:
{
  "message": "Bet deleted successfully."
}
```

## Testing the Feature

### Manual Test Steps
1. Log in as admin
2. Select "Cash-In" mode
3. Place a bet (or use existing bets)
4. Click "Bet History" tab
5. Verify bets appear in list
6. Click "Remove" on a bet
7. Confirm deletion
8. Verify bet is removed
9. Place new bet and verify history updates

### Expected Behavior
- ✅ Bets display with all details
- ✅ Delete button appears on each bet
- ✅ Confirmation dialog works
- ✅ Successful deletion removes bet from list
- ✅ History auto-refreshes after deletion
- ✅ History auto-refreshes after new bet
- ✅ Error messages display on failure
- ✅ Loading indicators show during operations

## Security Features
- All endpoints require admin authentication
- Users can only delete their own bets
- All deletions are audit logged
- Proper authorization checks in place

## Files Modified
1. `app/Http/Controllers/Api/BetController.php` - Added 2 methods
2. `routes/api.php` - Added 2 routes
3. `app/src/main/java/.../data/api/ApiServices.kt` - Added 2 methods
4. `viewmodel/CashInViewModel.kt` - Added state + 2 methods
5. `app/src/main/java/.../ui/admin/AdminCashInScreen.kt` - Added UI components

## Deployment Checklist
- [ ] All PHP syntax checks pass
- [ ] All Kotlin compilation succeeds
- [ ] Backend routes are registered
- [ ] API endpoints are accessible
- [ ] Admin can login
- [ ] Bets display in history
- [ ] Delete functionality works
- [ ] Audit logs record deletions
- [ ] Error handling works properly

## Troubleshooting

### Bets not showing
- Check admin is authenticated
- Verify token is valid
- Check API endpoint is registered

### Delete button not working
- Ensure admin role is set
- Check network connection
- Verify bet ID is correct

### History not refreshing
- Check LaunchedEffect is triggered
- Verify API response is successful
- Check state flow is being updated

## Support Contact
For issues or questions, contact the development team.

---
**Version**: 1.0  
**Last Updated**: May 22, 2026  
**Status**: ✅ Ready for Deployment
