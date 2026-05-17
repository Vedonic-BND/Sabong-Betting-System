# "Runner Accepted" Notification Issue - Root Cause Analysis

**Date:** May 17, 2026  
**Issue:** Teller (Teller's phone in CashInViewModel) receives "Runner Accepted" notifications even though they didn't manually assign the runner.

---

## Executive Summary

The issue is that the system is functioning **correctly by design**, but the behavior is confusing because:

1. **Runners initiate acceptance** - Runners (not tellers) accept assistance requests when they hear a request come through
2. **Tellers receive notifications of acceptance** - When ANY runner accepts a teller's request, that teller gets a "Runner Accepted" notification
3. **The teller didn't "assign" the runner** - The teller just made a request, and the runner who responded fastest got assigned

This is a **broadcast notification design** where all notifications about a request go to all relevant parties automatically.

---

## System Architecture Overview

### Three Roles in the Notification System

```
┌──────────────────────────────────────────────────────────────────┐
│ TELLER (CashInViewModel)                                         │
│ - Sends assistance request                                       │
│ - Receives: "Runner Accepted" when any runner accepts            │
│ - Initiates: requestRunner()                                     │
└──────────────────────────────────────────────────────────────────┘
                            ▲
                            │ RequestRunner broadcasts
                            │ to ReverbManager.onCashRequested
                            │
┌──────────────────────────────────────────────────────────────────┐
│ RUNNERS (RunnerViewModel)                                        │
│ - Receive incoming requests via ReverbManager.onCashRequested    │
│ - Press "Accept" button to accept request                        │
│ - Sends accept() HTTP POST to backend                            │
│ - Receives: "Request Accepted" confirmation                      │
└──────────────────────────────────────────────────────────────────┘
                            │
                            │ Runner accept() broadcasts
                            │ to ReverbManager.onRunnerAccepted
                            ▼
┌──────────────────────────────────────────────────────────────────┐
│ TELLER receives broadcast (onRunnerAccepted)                     │
│ - Gets "Runner Accepted" notification                            │
│ - Shows: "{RunnerName} has been assigned to {TellerName}."       │
└──────────────────────────────────────────────────────────────────┘
```

---

## How the Flow Currently Works

### Step-by-Step Process

#### Step 1: Teller Requests Runner
```
CashInScreen.kt
  ↓
CashInViewModel.requestRunner()
  ↓
HTTP POST to /api/assistance/request
  {
    "request_type": "assistance|need_cash|collect_cash|other",
    "custom_message": "optional"
  }
```

**Backend (RunnerAssistanceController::request):**
```php
1. Validates request
2. Creates CashRequest in database
3. Broadcasts CashRequestCreated event
   - Event type: "cash.request.created"
   - Channel: "fights" (all runners listening)
   - Payload: Full CashRequest details
4. Saves "Assistance Needed" notification to OWNER database
```

#### Step 2: Runner Receives Request (Real-time via WebSocket)
```
ReverbManager listens to "fights" channel
  ↓
Receives "cash.request.created" event
  ↓
Calls ReverbManager.onCashRequested callback
  ↓
RunnerViewModel captures in setupRealtimeListener():

    ReverbManager.onCashRequested = { data ->
        viewModelScope.launch(Dispatchers.Main) {
            val tellerName = data.optString("teller_name", "A teller")
            // ... format message ...
            
            addNotification(
                title = "Runner Requested",
                message = "$tellerName - $displayMessage",
                data = data,
                context = context
            )
            
            triggerSoundAndVibration(context)
        }
    }
```

**At this point:** Runner hears sound/vibration and sees "Runner Requested" in their notification list.

#### Step 3: Runner Accepts Request (Active Decision)
```
RunnerScreen.kt
  ↓
Runner presses "Accept" button
  ↓
HTTP POST to /api/assistance/accept/{tellerId}
```

**Backend (RunnerAssistanceController::accept):**
```php
1. Validates runner role
2. Checks if already accepted (race condition prevention)
3. Uses Cache::add() with 30-second timeout for locking
4. Creates CashRequest record with status = 'approved'
5. Broadcasts RunnerAccepted event
   - Event type: "runner.accepted"
   - Channel: "fights"
   - Payload: runner_name, runner_id, teller_name, teller_id, etc.
6. Saves THREE notifications to database:
   a. "Runner Accepted" → Teller (user_id = teller_id)
   b. "Request Accepted" → Runner (user_id = runner_id)
   c. "Assignment Successful" → All Owners (user_id = owner_id)
```

#### Step 4: Teller Receives "Runner Accepted" Broadcast

This is where you see the notification!

```
ReverbManager listens to "fights" channel
  ↓
Receives "runner.accepted" event
  ↓
Calls ReverbManager.onRunnerAccepted callback
  ↓
CashInViewModel captures in setupRealtimeListener():

    ReverbManager.onRunnerAccepted = { data ->
        viewModelScope.launch(Dispatchers.Main) {
            val assignedRunnerName = data.optString("runner_name", "A runner")
            val tellerName = data.optString("teller_name", "A teller")
            
            addNotification(
                title = "Request Assigned",
                message = "$assignedRunnerName has been assigned to $tellerName.",
                data = data,
                context = context
            )
        }
    }
```

---

## Visual Timeline

```
┌─────────────────────────────────────────────────────────────────┐
│ TIME 1: Teller requests assistance                              │
├─────────────────────────────────────────────────────────────────┤
│ Teller Screen: Presses "Request Runner" button                  │
│ → CashInViewModel.requestRunner()                               │
│ → HTTP POST /api/assistance/request                             │
│                                                                  │
│ Backend Event: Broadcasts CashRequestCreated                    │
│ → All runners receive via WebSocket (onCashRequested)           │
│ → Runners see: "Runner Requested: Assistance needed"            │
│ → Runners hear: Sound + Vibration                               │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ TIME 2: Runner accepts (within 30 seconds)                      │
├─────────────────────────────────────────────────────────────────┤
│ Runner Screen: Presses "Accept" button                          │
│ → RunnerScreen.kt calls accept()                                │
│ → HTTP POST /api/assistance/accept/{tellerId}                   │
│                                                                  │
│ Backend Event: Broadcasts RunnerAccepted                        │
│ → All tellers receive via WebSocket (onRunnerAccepted)          │
│ → TELLER sees: "Request Assigned: {RunnerName} assigned"        │
│                                                                  │
│ Backend also saves 3 notifications to database:                 │
│ 1. Teller notification (in database)                            │
│ 2. Runner notification (in database)                            │
│ 3. Owner notification (in database)                             │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ TIME 3: Polling fallback (if WebSocket disconnects)             │
├─────────────────────────────────────────────────────────────────┤
│ If WebSocket down for 30+ seconds:                              │
│ → CashInViewModel.loadSavedNotifications()                      │
│ → HTTP GET /api/notifications                                   │
│ → Retrieves all notifications from database (including           │
│    "Runner Accepted" saved in TIME 2)                           │
│ → Displays any new ones not already in local list               │
└─────────────────────────────────────────────────────────────────┘
```

---

## Why You're Seeing Multiple Notifications

### Scenario: Why a Teller Sees "Runner Accepted" Without Assigning

**This is actually correct behavior:**

1. **Teller broadcasts request** → "I need assistance!"
   - Request goes out to ALL runners via WebSocket

2. **Fastest runner clicks Accept** → "I'll help!"
   - Backend broadcasts to ALL tellers: "Runner X accepted your request"

3. **ALL tellers who requested get the notification** (including yours)
   - This is the WebSocket broadcast design

**You might be confused because:**
- You didn't manually click a button to assign the runner
- The runner self-assigned by accepting the request
- But the teller always gets the acceptance notification

---

## Notification Flow in Code

### In CashInViewModel.kt

```kotlin
fun setupRealtimeListener(context: Context) {
    // ... other listeners ...
    
    // This fires when ANY runner accepts ANY teller's request
    ReverbManager.onRunnerAccepted = { data ->
        viewModelScope.launch(Dispatchers.Main) {
            val assignedRunnerName = data.optString("runner_name", "A runner")
            val tellerName = data.optString("teller_name", "A teller")
            
            // ⚠️ ISSUE: No checking if THIS teller requested or if another teller requested!
            // This notification appears for ALL tellers because it's a broadcast
            addNotification(
                title = "Request Assigned",
                message = "$assignedRunnerName has been assigned to $tellerName.",
                data = data,
                context = context
            )
        }
    }
}
```

### In RunnerViewModel.kt

Same pattern - receives broadcast for all runners:

```kotlin
ReverbManager.onRunnerAccepted = { data ->
    viewModelScope.launch(Dispatchers.Main) {
        val assignedRunnerName = data.optString("runner_name", "A runner")
        val tellerName = data.optString("teller_name", "A teller")
        
        // ⚠️ ISSUE: This is a broadcast - ALL runners see this
        // Even runners who didn't accept the request
        addNotification(
            title = "Request Assigned",
            message = "$assignedRunnerName has been assigned to $tellerName.",
            data = data,
            context = context
        )
    }
}
```

---

## The Real Problems (Design Issues)

### Problem 1: Broadcast to Everyone (Inefficient)
- **Current:** When runner accepts, broadcast goes to ALL tellers and ALL runners
- **Issue:** Notification spam - you see acceptances for OTHER tellers' requests
- **Example:** 
  - You request help at 2:00 PM
  - Runner X accepts at 2:01 PM → You see "Runner X assigned"
  - You request help again at 2:05 PM
  - Runner Y accepts at 2:06 PM → You see "Runner Y assigned" (correct)
  - But if another teller also requested, you'll see their runner acceptances too!

### Problem 2: No Filtering for Specific User
```kotlin
// Current code in CashInViewModel.kt:
ReverbManager.onRunnerAccepted = { data ->
    // This has NO check for:
    // - Is this acceptance for MY request?
    // - Is the teller_id in the data equal to MY user_id?
    
    // Result: You get notifications for OTHER tellers' acceptances too
    addNotification(...)
}
```

### Problem 3: Duplicate Notifications (Real-time + Polling)
If your WebSocket drops and reconnects:
1. **Real-time (WebSocket)** → Notification appears instantly
2. **Polling (30s fallback)** → API call retrieves same notification from database
3. **Result:** Same notification appears twice

---

## Current Notification Delivery Paths

### Path 1: Real-Time WebSocket (Primary)
```
Backend broadcasts event
  ↓
ReverbManager receives on "fights" channel
  ↓
Calls onRunnerAccepted callback
  ↓
CashInViewModel.setupRealtimeListener() processes
  ↓
Notification added to _notifications StateFlow
  ↓
UI updates instantly (< 100ms)
```

### Path 2: Polling Fallback (30+ seconds)
```
WebSocket disconnected for 30+ seconds
  ↓
CashInViewModel.loadSavedNotifications() triggers
  ↓
HTTP GET /api/notifications
  ↓
Backend returns all notifications from database
  ↓
Notification added to _notifications StateFlow
  ↓
UI updates (delayed, but recovers missed notifications)
```

### Path 3: Database Persistence
```
Backend creates Notification record in database
  ↓
When you start the app
  ↓
CashInViewModel.loadSavedNotifications() on init
  ↓
HTTP GET /api/notifications
  ↓
Displays all notifications including old ones
```

---

## Database Notifications Table

All notifications are saved to database:

```sql
notifications
├── id (primary key)
├── user_id (recipient: teller_id, runner_id, or owner_id)
├── title ("Runner Accepted", "Request Assigned", etc.)
├── message (human-readable message)
├── data (JSON: contextual information)
├── is_read (boolean)
└── created_at (timestamp)
```

When `RunnerAssistanceController::accept()` completes, it creates:

```php
// For the teller whose request was accepted:
Notification::create([
    'user_id' => $teller->id,  // YOUR teller ID
    'title' => 'Runner Accepted',
    'message' => "{$runner->name} is on the way.",
    ...
]);

// For all other runners:
Notification::create([
    'user_id' => $runner->id,  // Other runners
    'title' => 'Request Accepted',
    'message' => "You have accepted the request from {$teller->name}.",
    ...
]);

// For all owners:
Notification::create([
    'user_id' => $owner->id,  // All owner IDs
    'title' => 'Assignment Successful',
    'message' => "{$runner->name} assigned to {$teller->name}",
    ...
]);
```

---

## Why Multiple "Runner Accepted" Notifications?

### Scenario Analysis

**Teller Session (You):**
```
2:00 PM → You send assistance request
2:01 PM → Runner A accepts
         → You receive WebSocket: "Runner A accepted"
         → You see notification: "Runner A assigned"

2:05 PM → WebSocket drops

2:35 PM → Another runner B accepts a request (from another teller)
         → You don't receive WebSocket (disconnected)

2:35 PM + 30s → Polling triggers
                → API returns all notifications from database
                → Includes "Runner A accepted" (already seen)
                → Includes "Runner B accepted" (other teller's request)
                → Both show in notification list

2:40 PM → WebSocket reconnects

2:45 PM → You send another assistance request
2:46 PM → Runner C accepts
         → You receive WebSocket: "Runner C accepted"
         → You see notification: "Runner C assigned"
```

### Why You See Acceptances You Didn't Initiate

The WebSocket broadcast system sends to:
- ALL connected tellers (not just the requester)
- ALL connected runners (not just the accepter)

```
Backend: event(new RunnerAccepted($cashRequest))
           ↓
         Broadcasts on "fights" channel
           ↓
         ├─ Runner A hears it
         ├─ Runner B hears it
         ├─ Runner C hears it
         ├─ Teller X hears it  ← You
         ├─ Teller Y hears it  ← Other teller
         ├─ Teller Z hears it  ← Another teller
         └─ Owner hears it
```

---

## Root Cause Summary

| Issue | Root Cause | Impact |
|-------|-----------|--------|
| **Seeing "Runner Accepted" for other tellers' requests** | Broadcast goes to ALL users, not specific user | Notification spam |
| **Missing "is_read" filtering on polling** | All notifications returned, no filtering for read/unread | Old notifications reappear after WebSocket drop |
| **Duplicate notifications** | WebSocket + polling return same notification | You see same notification twice |
| **No user_id check in onRunnerAccepted** | CashInViewModel processes all broadcasts | You get notifications meant for other tellers |

---

## Recommended Fixes

### Fix 1: Filter Notifications by Current User
**Location:** `CashInViewModel.kt` - `setupRealtimeListener()`

```kotlin
ReverbManager.onRunnerAccepted = { data ->
    viewModelScope.launch(Dispatchers.Main) {
        val assignedTellerId = data.optLong("teller_id", -1)
        
        // Only show if this notification is for the current user
        if (assignedTellerId == currentUserId) {  // Add this check
            val assignedRunnerName = data.optString("runner_name", "A runner")
            val tellerName = data.optString("teller_name", "A teller")
            
            addNotification(
                title = "Request Assigned",
                message = "$assignedRunnerName has been assigned to $tellerName.",
                data = data,
                context = context
            )
        }
    }
}
```

### Fix 2: Use Private Channel Instead of Broadcast
**Current (inefficient):** Channel "fights" (everyone hears everything)  
**Better:** Channel "private-notifications-{user_id}" (only targeted user hears)

### Fix 3: Mark Notifications as Read
**Issue:** Polling returns all notifications, including old ones  
**Solution:** When notification is shown, mark as_read = true, then filter polling results to only unread

### Fix 4: Deduplication on Polling
Keep track of notification IDs already seen:
```kotlin
val existingIds = _notifications.value.map { it.id }.toSet()
val newNotifications = savedNotifications.filter { it.id !in existingIds }
```

---

## Current Behavior vs Expected Behavior

### Current (Broadcast - Inefficient)
```
Runner accepts request
  ↓
Broadcast goes to: ALL tellers, ALL runners, ALL owners
  ↓
Everyone sees: "{RunnerName} has been assigned"
  ↓
Result: SPAM - You see other tellers' assignments
```

### Expected (Targeted - Efficient)
```
Runner accepts request for Teller X
  ↓
Private message to Teller X
  ↓
Only Teller X sees: "{RunnerName} has been assigned to you"
  ↓
Result: RELEVANT - You see only YOUR assignments
```

---

## Summary

**What's Happening:**
1. When ANY runner accepts ANY request, a broadcast event fires
2. ALL tellers (including you) receive this broadcast via WebSocket
3. Your CashInViewModel has no filter to check if the notification is for you
4. Result: You see "Runner Accepted" notifications for other tellers' requests

**Why It's Confusing:**
- You didn't request it, so why are you getting the notification?
- Because the system broadcasts to everyone, not just the relevant user

**Quick Fix:**
Add a check to filter notifications by `teller_id` matching your `currentUserId`

**Better Solution:**
Switch to private channels instead of broadcast to avoid notification spam

---

**Document Status:** Complete  
**Last Updated:** May 17, 2026  
**Created By:** GitHub Copilot
