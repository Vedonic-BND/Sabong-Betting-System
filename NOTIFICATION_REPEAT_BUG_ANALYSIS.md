# Notification Repeat Issue - Root Cause Analysis

**Date:** May 17, 2026  
**Issue:** Tellers see notifications repeat every few seconds, even when owner hasn't assigned a runner yet

---

## Problem Description

You're seeing the same notification popup repeatedly:
- First time: Runner gets assigned (correct)
- Every 30 seconds: Same notification appears again (WRONG)
- Repeats until notification is marked as read

---

## Root Cause

### The Loop

```
1. CashInScreen loads (LaunchedEffect)
2. Calls: cashInViewModel.loadSavedNotifications()
3. Starts polling (if WebSocket down)
4. Every 30 seconds: loadSavedNotifications() fetches from API
5. API returns ALL notifications (including old ones from database)
6. _notifications StateFlow updates
7. LaunchedEffect(notifications) TRIGGERS
8. Finds "Runner Assigned" notification with !isRead
9. Shows popup again ❌
10. Go back to step 4...
```

### Code Issue

**CashInScreen.kt:**
```kotlin
LaunchedEffect(notifications) {  // ← Triggers on EVERY change
    val runnerAssignedNotif = notifications.find { 
        it.title == "Runner Assigned" && !it.isRead  // ← Checks !isRead
    }
    if (runnerAssignedNotif != null) {
        showRunnerNotification = true  // ← Shows popup
        cashInViewModel.markNotificationAsRead(runnerAssignedNotif.id)
        
        delay(3000)
        showRunnerNotification = false
    }
}
```

**Problem:** 
- User dismisses popup
- `markNotificationAsRead()` called
- But when polling refreshes, **old notification is re-added from database** without marking read status properly
- Popup shows again!

---

## Why It Happens

### Polling Fetches from Database

```kotlin
// CashInViewModel.kt
val response = RetrofitClient.api.getNotifications(token)
val savedNotifications = response.body()?.map { notif ->
    TellerNotification(
        id = notif.id.toString(),
        title = notif.title,
        message = notif.message,
        isRead = notif.is_read  // ← From database
    )
}
```

**Issue:** When notification is marked as read in UI, it's not synced back to database immediately!

### Sequence of Events

```
1. Notification appears (from WebSocket or polling)
2. User sees popup
3. cashInViewModel.markNotificationAsRead() called
4. Only updates LOCAL _notifications.value (UI state)
5. After 3 seconds, LaunchedEffect completes
6. 30 seconds later: Polling fetches from API/database
7. Notification is still "is_read = false" in database! ❌
8. Gets re-added to UI as new notification
9. LaunchedEffect triggers again
10. Popup shows again
```

---

## Solution: Stop Re-showing Read Notifications

### Fix Option 1: Track Shown Notifications (Simple)

Add a set to track notifications we've already shown:

```kotlin
val shownNotificationIds = remember { mutableSetOf<String>() }

LaunchedEffect(notifications) {
    val runnerAssignedNotif = notifications.find { 
        it.title == "Runner Assigned" && 
        !it.isRead &&
        it.id !in shownNotificationIds  // ← NEW: Skip if already shown
    }
    if (runnerAssignedNotif != null) {
        shownNotificationIds.add(runnerAssignedNotif.id)  // ← Track it
        acceptedRunnerName = runnerAssignedNotif.message
        showRunnerNotification = true
        cashInViewModel.markNotificationAsRead(runnerAssignedNotif.id)
        
        delay(3000)
        showRunnerNotification = false
    }
}
```

### Fix Option 2: Sync Mark-as-Read to Database (Better)

When marking notification as read, also update database:

```kotlin
fun markNotificationAsRead(id: String) {
    _notifications.value = _notifications.value.map {
        if (it.id == id) it.copy(isRead = true) else it
    }
    
    // ← NEW: Also mark as read in database
    viewModelScope.launch {
        try {
            val token = bearerToken(context)
            RetrofitClient.api.markNotificationAsRead(token, id)
        } catch (e: Exception) {
            Log.e("CashInVM", "Failed to mark notification as read", e)
        }
    }
}
```

### Fix Option 3: Don't Re-show During Same Session (Hybrid)

Skip showing if already shown in this session:

```kotlin
private val shownNotificationIds = mutableSetOf<String>()

LaunchedEffect(notifications) {
    val runnerAssignedNotif = notifications.find { 
        it.title == "Runner Assigned" && 
        !it.isRead &&
        it.id !in shownNotificationIds  // ← Don't re-show in session
    }
    if (runnerAssignedNotif != null) {
        shownNotificationIds.add(runnerAssignedNotif.id)
        // Show popup...
    }
}
```

---

## Recommended Fix

**Use Fix Option 2 (Sync to Database)** - This is the most complete solution:

1. **When notification is marked as read locally**, also update database
2. **Next polling cycle** fetches `is_read = true` from database
3. **LaunchedEffect filter** (`!it.isRead`) excludes it
4. **Popup doesn't re-show** ✓

### Changes Needed

**1. Add API endpoint** (if not exists):
```php
// routes/api.php
Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
```

**2. Update CashInViewModel**:
```kotlin
fun markNotificationAsRead(id: String, context: Context? = null) {
    // Update local state immediately
    _notifications.value = _notifications.value.map {
        if (it.id == id) it.copy(isRead = true) else it
    }
    
    // Also update database asynchronously
    if (context != null) {
        viewModelScope.launch {
            try {
                val token = bearerToken(context)
                RetrofitClient.api.markNotificationAsRead(token, id)
            } catch (e: Exception) {
                Log.e("CashInVM", "Failed to sync read status", e)
            }
        }
    }
}
```

**3. Update CashInScreen**:
```kotlin
if (runnerAssignedNotif != null) {
    acceptedRunnerName = runnerAssignedNotif.message
    showRunnerNotification = true
    // Pass context to sync to database
    cashInViewModel.markNotificationAsRead(runnerAssignedNotif.id, context)
    
    delay(3000)
    showRunnerNotification = false
}
```

---

## Why This Happens

The system was designed to:
1. Show notifications real-time via WebSocket
2. Fall back to polling if WebSocket down
3. Mark as read in UI

**But forgot:** When polling re-fetches, old notifications come back because they weren't synced to database!

---

## Test Case

**Before Fix:**
```
1. Get notification (from owner assign)
2. Popup shows
3. Popup auto-dismisses (3 seconds)
4. After 30 seconds: Same notification appears again
5. User frustrated by repeated popups ❌
```

**After Fix:**
```
1. Get notification (from owner assign)
2. Popup shows
3. markNotificationAsRead() syncs to database
4. After 30 seconds: Polling doesn't re-fetch this notification (is_read=true)
5. Popup doesn't re-show ✓
```

---

## Summary

**Problem:** Notifications re-show every 30 seconds when polling refreshes  
**Root Cause:** Marked as read locally, but database still has is_read=false  
**Solution:** Sync read status to database when marking as read  
**Implementation:** Add API call in markNotificationAsRead()

---

**Document Status:** Analysis Complete  
**Recommendation:** Implement Fix Option 2 (database sync)
