# Request and Notification Feature Analysis

**Date:** May 15, 2026  
**Scope:** Sabong Betting System - Web & Android Apps

---

## Table of Contents

1. [Overview](#overview)
2. [Web App Architecture](#web-app-architecture)
3. [Android App Architecture](#android-app-architecture)
4. [Request Flow](#request-flow)
5. [Notification Flow](#notification-flow)
6. [Data Models](#data-models)
7. [Real-Time Communication](#real-time-communication)
8. [API Endpoints](#api-endpoints)
9. [Issues & Observations](#issues--observations)
10. [Recommendations](#recommendations)

---

## Overview

The Sabong Betting System implements a **request-response notification system** that enables:
- **Tellers** to request runner assistance for cash handling
- **Runners** to receive and accept assistance requests
- **Owners** to manage and monitor runner assignments
- **Real-time notifications** via WebSocket (Reverb) and database persistence

### Key Participants

| Role | Request Ability | Notification Types |
|------|-----------------|-------------------|
| **Teller** | ✅ Request runners for assistance/cash | Runner Assigned, Request Assigned |
| **Runner** | ❌ No requests | Runner Requested, New Assignment |
| **Owner** | ✅ Manual runner assignment | Assignment Successful |
| **Admin** | ✅ All privileges | All notification types |

---

## Web App Architecture

### Core Components

#### 1. **Controllers**

**`app/Http/Controllers/Api/CashRequestController.php`**
- **Purpose:** Handle teller cash requests
- **Key Methods:**
  - `store()` - Create new cash request
  - `index()` - Retrieve pending cash requests
  - `accept()` - Runner accepts request
  - `complete()` - Runner completes transaction

**`app/Http/Controllers/Api/RunnerAssistanceController.php`**
- **Purpose:** Manage runner assistance flow
- **Key Methods:**
  - `request()` - Teller sends assistance request
  - `accept()` - Runner accepts assistance

**`app/Http/Controllers/Api/NotificationController.php`**
- **Purpose:** Notification persistence and retrieval
- **Key Methods:**
  - `store()` - Save notification to database
  - `index()` - Retrieve user's notifications
  - `markAsRead()` - Mark notification as read
  - `clear()` - Delete all notifications

**`app/Http/Controllers/Owner/NotificationController.php`**
- **Purpose:** Owner-specific notification management
- **Key Methods:**
  - `index()` - Display assignment requests
  - `assignRunner()` - Manually assign runner to teller
  - `getAvailableRunners()` - Get list of available runners

#### 2. **Models**

**`app/Models/CashRequest.php`**
```php
Fields:
- teller_id (who requested)
- runner_id (who accepted/assigned)
- type: 'cash_in' | 'cash_out'
- amount: decimal
- reason: string
- request_type: 'assistance' | 'need_cash' | 'collect_cash' | 'other'
- custom_message: string (for 'other' type)
- status: 'pending' | 'approved' | 'completed' | 'rejected'
- approved_at, completed_at (timestamps)
- approved_by, completed_by (user IDs)
```

**`app/Models/Notification.php`**
```php
Fields:
- user_id (recipient)
- title: string
- message: string
- data: json (contextual information)
- is_read: boolean
- created_at, updated_at
```

#### 3. **Events (Broadcasting)**

**`app/Events/CashRequestCreated.php`**
- Broadcast on: `cash-requests` channel
- Event name: `cash.request.created`
- Payload: Cash request details

**`app/Events/RunnerAccepted.php`**
- Broadcast on: `cash-requests` channel
- Event name: `runner.accepted`
- Payload: Runner ID, Teller ID, Assignment details

**Other Events:**
- `BetPlaced` - When bet is placed
- `FightUpdated` - When fight status changes
- `TellerCashStatusUpdated` - When teller's cash updates
- `WinnerDeclared` - When fight winner declared
- `SideStatusUpdated` - When fight side status changes

#### 4. **Request Types & Message Mapping**

```php
'assistance' => 'Assistance needed at counter'
'need_cash' => 'Runner needed - Need cash'
'collect_cash' => 'Runner needed - Collect excess cash'
'other' => 'Custom request: {customMessage}'
```

---

## Android App Architecture

### Core Components

#### 1. **ViewModels**

**`RunnerViewModel.kt`**
- **Purpose:** Manage runner's requests and notifications
- **State:**
  - `tellers: StateFlow<List<TellerCashStatus>>` - Available tellers
  - `history: StateFlow<List<RunnerTransactionResponse>>` - Transaction history
  - `notifications: StateFlow<List<RunnerNotification>>` - Notification list
  - `incomingRequest: StateFlow<JSONObject?>` - Current active request
  - `activeRequest: StateFlow<JSONObject?>` - Same as incomingRequest

- **Key Functions:**
  - `loadTellers()` - Fetch list of tellers
  - `loadHistory()` - Fetch transaction history
  - `loadSavedNotifications()` - Fetch notifications from API
  - `setupRealtimeListener()` - Connect to WebSocket events
  - `addNotification()` - Add notification to state and save to DB
  - `createTransaction()` - Record transaction (cash in/out)
  - `markNotificationAsRead()` - Mark as read
  - `clearNotifications()` - Clear all notifications

**`CashInViewModel.kt`**
- **Purpose:** Manage teller's cash operations and requests
- **State:**
  - `currentFight: StateFlow<Fight?>` - Current active fight
  - `requestSuccess: StateFlow<Boolean>` - Request successful flag
  - `notifications: StateFlow<List<TellerNotification>>` - Notification list
  - `tellerCashStatus: StateFlow<TellerCashStatusResponse?>` - Cash balance info
  - `runnerHistory: StateFlow<List<RunnerTransactionResponse>>` - Runner transactions

- **Key Functions:**
  - `loadCurrentFight()` - Fetch current fight details
  - `placeBet()` - Place bet on fight
  - `requestRunner()` - Send assistance request
  - `loadTellerCashStatus()` - Get cash balance
  - `loadRunnerHistory()` - Get recent transactions with runners
  - `addNotification()` - Add notification to state and save to DB
  - `loadSavedNotifications()` - Fetch notifications from API

**`ReverbViewModel.kt`**
- **Purpose:** Manage real-time WebSocket connections
- **State:**
  - `connected: StateFlow<Boolean>` - Connection status
  - `fightState: StateFlow<ReverbFightState?>` - Fight state updates
  - `runnerAccepted: StateFlow<JSONObject?>` - Runner acceptance event
  - `runnerDeclined: StateFlow<JSONObject?>` - Runner declined event
  - `cashUpdated: StateFlow<JSONObject?>` - Cash update events

- **Key Callbacks:**
  - `onConnected` - Connection established
  - `onFightUpdated` - Fight state changed
  - `onRunnerAccepted` - Runner accepted request
  - `onRunnerDeclined` - Runner declined request
  - `onCashRequested` - Incoming cash/assistance request
  - `onTellerCashUpdated` - Teller's cash balance updated

#### 2. **Data Models**

**`RunnerNotification.kt`**
```kotlin
data class RunnerNotification(
    val id: String = UUID.randomUUID().toString(),
    val title: String,
    val message: String,
    val timestamp: String,
    val data: JSONObject? = null,
    var isRead: Boolean = false
)
```

**`TellerNotification.kt`**
```kotlin
data class TellerNotification(
    val id: String = UUID.randomUUID().toString(),
    val title: String,
    val message: String,
    val timestamp: String,
    val data: JSONObject? = null,
    var isRead: Boolean = false
)
```

#### 3. **Real-Time Connection**

**`ReverbManager`** (Singleton)
- Manages WebSocket connection to Reverb server
- Maintains callbacks for different events
- Handles reconnection logic
- Listens on multiple channels: `private-notifications`, `cash-requests`, etc.

#### 4. **API Integration**

**`RetrofitClient.api`** - HTTP endpoints:
- `saveNotification()` - POST /api/notifications
- `getNotifications()` - GET /api/notifications
- `sendAssistanceRequest()` - POST /api/assistance/request
- `getTellersCashStatus()` - GET /runner/tellers
- `getTellerRunnerTransactions()` - GET /teller/runner-transactions
- `getRunnerHistory()` - GET /runner/history

#### 5. **UI Components**

**`RunnerScreen.kt`**
- Displays list of tellers needing assistance
- Shows transaction history
- Displays notification alerts with sound/vibration
- Handles incoming request popups

**`RequestRunnerScreen.kt`**
- Allows teller to select request type
- Sends assistance request
- Shows success confirmation

---

## Request Flow

### Flow Diagram: Teller Requests Runner

```
┌─────────────────────────────────────────────────────────────────┐
│ TELLER                                                          │
└──────────┬──────────────────────────────────────────────────────┘
           │
           │ 1. Click "Request Runner"
           │    (RequestRunnerScreen)
           ▼
┌─────────────────────────────────────────────────────────────────┐
│ Android: CashInViewModel.requestRunner()                        │
│   POST /api/assistance/request                                  │
│   {                                                              │
│     "request_type": "assistance|need_cash|collect_cash|other"   │
│     "custom_message": "optional"                                │
│   }                                                              │
└──────────┬──────────────────────────────────────────────────────┘
           │
           │ 2. HTTP POST Request
           ▼
┌─────────────────────────────────────────────────────────────────┐
│ Laravel: RunnerAssistanceController::request()                  │
│   - Validates request                                           │
│   - Checks teller cooldown (30s)                                │
│   - Creates CashRequest in DB                                   │
│   - Creates Notification for Owner                              │
└──────────┬──────────────────────────────────────────────────────┘
           │
           │ 3. Broadcast Event
           ▼
┌─────────────────────────────────────────────────────────────────┐
│ WebSocket: CashRequestCreated Event                             │
│   - Channel: cash-requests                                      │
│   - Payload: Full cash request details                          │
└──────────┬──────────────────────────────────────────────────────┘
           │
           ├─────────────────────────────────┬────────────────────┤
           │                                 │                    │
           │ 4a. Runners receive            │ 4b. Save to DB     │
           ▼                                 ▼                    ▼
┌──────────────────┐            ┌──────────────────────────────────┐
│ ReverbManager    │            │ NotificationController::store()  │
│ onCashRequested  │            │ Saves notification to DB         │
│ callback fired   │            │ for each runner                  │
└──────────────────┘            └──────────────────────────────────┘
           │
           │ 5. Display Alert to Runners
           ▼
┌─────────────────────────────────────────────────────────────────┐
│ Android RunnerScreen                                            │
│   - Sound notification plays                                    │
│   - Device vibrates                                             │
│   - incomingRequest StateFlow updates                           │
│   - Alert dialog shows request details                          │
└─────────────────────────────────────────────────────────────────┘
```

### Step-by-Step Process

| Step | Component | Action |
|------|-----------|--------|
| 1 | Teller App | Opens RequestRunnerScreen |
| 2 | Teller Selects | Request type (assistance/need_cash/collect_cash/other) |
| 3 | CashInViewModel | Calls `requestRunner()` |
| 4 | API | HTTP POST to `/api/assistance/request` |
| 5 | Server | `RunnerAssistanceController::request()` validates & creates CashRequest |
| 6 | Server | Broadcasts `CashRequestCreated` event on `cash-requests` channel |
| 7 | Server | Creates Notification record for all runners in DB |
| 8 | Runners | Receive real-time notification via WebSocket |
| 9 | Runners | Can accept via `/api/assistance/accept/{tellerId}` |
| 10 | Server | Broadcasts `RunnerAccepted` event with runner info |
| 11 | Teller | Receives notification that runner accepted |

---

## Notification Flow

### Database Persistence

```
┌────────────────────────────────────────────┐
│ Event Generated                             │
│ (CashRequestCreated, RunnerAccepted, etc.) │
└────────────────┬───────────────────────────┘
                 │
                 ├─► Broadcast on WebSocket (Real-time)
                 │
                 └─► Save to notifications table (Database)
                      │
                      ├─ user_id (recipient)
                      ├─ title (notification title)
                      ├─ message (notification message)
                      ├─ data (JSON context)
                      ├─ is_read (false by default)
                      └─ created_at (timestamp)
```

### Notification Types

#### 1. **Runner Requested** (To Owner/Admin)
- **Trigger:** Teller sends assistance request
- **Title:** "Assistance Needed"
- **Recipients:** Owner, Admin

#### 2. **Runner Request** (To Runners)
- **Trigger:** Teller sends assistance request
- **Title:** "Runner Request"
- **Message:** "{tellerName} - {requestMessage}"
- **Data:**
  ```json
  {
    "teller_id": 123,
    "teller_name": "John",
    "cash_request_id": 456,
    "request_type": "assistance"
  }
  ```

#### 3. **Runner Assigned** (To Teller)
- **Trigger:** Runner accepts request or owner manually assigns
- **Title:** "Runner Assigned"
- **Message:** "{runnerName} is on the way..."
- **Data:**
  ```json
  {
    "runner_id": 789,
    "runner_name": "Pedro",
    "request_type": "assistance"
  }
  ```

#### 4. **New Assignment** (To Runner)
- **Trigger:** Owner manually assigns runner to teller
- **Title:** "New Assignment"
- **Message:** "You have been assigned to assist {tellerName}..."
- **Data:**
  ```json
  {
    "teller_id": 123,
    "teller_name": "John",
    "request_type": "assistance"
  }
  ```

#### 5. **Assignment Successful** (To Owner)
- **Trigger:** Runner assigned successfully
- **Title:** "Assignment Successful"
- **Message:** "{runnerName} has been assigned to {tellerName}..."

### Notification Retrieval

**Android Apps:**
```kotlin
// Periodic polling every 2-3 seconds
viewModel.loadSavedNotifications(context)

// Real-time updates via WebSocket
ReverbManager.onCashRequested = { ... }
ReverbManager.onRunnerAccepted = { ... }
```

**Web App:**
```php
// GET /api/notifications
// Returns all notifications for authenticated user
```

---

## Data Models

### CashRequest (Relational)

| Field | Type | Purpose |
|-------|------|---------|
| id | PK | Unique identifier |
| teller_id | FK → User | Who requested |
| runner_id | FK → User | Who accepted/assigned |
| type | Enum | `cash_in` \| `cash_out` |
| amount | Decimal | Cash amount |
| reason | String | Why (optional) |
| request_type | Enum | `assistance` \| `need_cash` \| `collect_cash` \| `other` |
| custom_message | String | For "other" type |
| status | Enum | `pending` \| `approved` \| `completed` \| `rejected` |
| approved_at | Timestamp | When approved |
| completed_at | Timestamp | When completed |
| approved_by | FK | Who approved (runner_id) |
| completed_by | FK | Who completed (runner_id) |

### Notification (Document-like)

| Field | Type | Purpose |
|-------|------|---------|
| id | PK | Unique identifier |
| user_id | FK → User | Recipient |
| title | String | Notification title |
| message | String | Human-readable message |
| data | JSON | Contextual information |
| is_read | Boolean | Read status |
| created_at | Timestamp | Creation time |

### RunnerNotification (Local - Android)

| Field | Type | Purpose |
|-------|------|---------|
| id | UUID | Client-side ID |
| title | String | Notification title |
| message | String | Message content |
| timestamp | String | Formatted time (hh:mm a) |
| data | JSONObject | Parsed context |
| isRead | Boolean | Read status |

---

## Real-Time Communication

### Reverb WebSocket Setup

**Configuration:** `config/reverb.php`
- Default port: 8000
- Broadcast host: 0.0.0.0
- Protocol: HTTP/WebSocket

### Channels

| Channel | Purpose | Subscribers |
|---------|---------|-------------|
| `cash-requests` | Cash/assistance requests | Runners, Owners, Admins |
| `private-notifications-{user_id}` | User-specific updates | Individual user |
| `presence-fight-{fight_id}` | Fight updates | All viewers |

### Events

| Event | Channel | Broadcast When |
|-------|---------|-----------------|
| `cash.request.created` | cash-requests | Teller requests assistance |
| `runner.accepted` | cash-requests | Runner accepts request |
| `fight.updated` | fight-updates | Fight state changes |
| `winner.declared` | fight-updates | Winner announced |
| `teller.cash.updated` | cash-requests | Teller's cash balance changes |

### Android WebSocket Handling

**ReverbManager Class:**
```kotlin
object ReverbManager {
    // Callbacks
    var onCashRequested: ((JSONObject) -> Unit)? = null
    var onRunnerAccepted: ((JSONObject) -> Unit)? = null
    var onConnected: (() -> Unit)? = null
    var onDisconnected: (() -> Unit)? = null
    
    fun connect()
    fun disconnect()
    fun send(event: String, data: JSONObject)
}
```

**Connection Lifecycle:**
1. App starts → `ReverbViewModel.connect()`
2. Set callbacks
3. Call `ReverbManager.connect()`
4. Listen for events
5. On event → Trigger callback
6. Update StateFlow
7. UI recomposes with new state

---

## API Endpoints

### Notification Endpoints

| Method | Endpoint | Purpose | Auth |
|--------|----------|---------|------|
| POST | `/api/notifications` | Save notification | ✅ |
| GET | `/api/notifications` | Get user's notifications | ✅ |
| PATCH | `/api/notifications/{id}/read` | Mark as read | ✅ |
| DELETE | `/api/notifications` | Clear all notifications | ✅ |

### Cash Request Endpoints

| Method | Endpoint | Purpose | Auth |
|--------|----------|---------|------|
| POST | `/api/cash-request` | Create cash request | ✅ Teller |
| GET | `/api/cash-requests` | Get pending requests | ✅ |
| POST | `/api/assistance/request` | Request assistance | ✅ Teller |
| POST | `/api/assistance/accept/{tellerId}` | Accept assignment | ✅ Runner |

### Runner Endpoints

| Method | Endpoint | Purpose | Auth |
|--------|----------|---------|------|
| GET | `/api/runner/tellers` | Get tellers status | ✅ Runner |
| GET | `/api/runner/history` | Get transaction history | ✅ Runner |
| POST | `/api/runner/transaction` | Record transaction | ✅ Runner |

### Teller Endpoints

| Method | Endpoint | Purpose | Auth |
|--------|----------|---------|------|
| GET | `/api/teller/cash-status` | Get cash balance | ✅ Teller |
| GET | `/api/teller/runner-transactions` | Get runner transactions | ✅ Teller |

---

## Issues & Observations

### 1. **Cooldown Mechanism**

**Issue:** Tellers have a 30-second cooldown after requesting a runner
- **Location:** `RunnerAssistanceController::request()`
- **Mechanism:** Cache key `teller_cooldown_{user_id}`
- **Observation:** Helps prevent request spam but may frustrate users if they legitimately need multiple runners

### 2. **Notification Polling**

**Issue:** Android app polls notifications every 2-3 seconds from database
- **Location:** `RunnerScreen.kt` and `CashInScreen.kt`
- **Concern:** Creates database load; WebSocket should be primary
- **Recommendation:** Use polling only as fallback

### 3. **Notification Deduplication**

**Issue:** Complex merge logic to prevent duplicate notifications
- **Location:** `RunnerViewModel.kt` - `loadSavedNotifications()`
- **Concern:** Relies on UUID/ID matching which may fail
- **Observation:** Attempts to merge local (in-memory) and database notifications

**Code:**
```kotlin
val existingIds = _notifications.value.map { it.id }.toSet()
val newNotifications = savedNotifications.filter { notif ->
    notif.id !in existingIds
}
```

### 4. **Real-Time Event Processing**

**Issue:** Multiple listeners may process same event
- **Location:** `ReverbViewModel.kt` and `RunnerViewModel.kt`
- **Concern:** Race conditions if multiple callbacks fire simultaneously

### 5. **Cache-Based Assignment**

**Issue:** Runner assignments stored in cache with 30-second expiration
- **Location:** `NotificationController::assignRunner()` (Owner portal)
- **Concern:** Assignment lost if cache expires before completion
- **Alternative:** Should use database with explicit expiration

**Code:**
```php
Cache::put($assignmentKey, [...], now()->addSeconds(30));
```

### 6. **Error Handling**

**Issue:** Limited error handling in WebSocket listeners
- **Location:** `ReverbManager` (no error/exception callbacks)
- **Concern:** Failed connections not explicitly handled

### 7. **Timestamp Formatting**

**Issue:** Inconsistent timestamp formats
- **Android:** SimpleDateFormat "hh:mm a"
- **Web:** Laravel Carbon format
- **Concern:** May cause display inconsistencies

### 8. **No Notification Expiry**

**Issue:** Notifications stored indefinitely
- **Location:** `Notification` model
- **Concern:** Database growth; old notifications accumulate
- **Recommendation:** Implement soft delete or archival strategy

### 9. **Runner Auto-Decline**

**Issue:** No automatic decline if runner doesn't respond
- **Observation:** Request stays "pending" indefinitely
- **Recommendation:** Add timeout mechanism

### 10. **Missing Validation**

**Issue:** Custom message not validated for length in RunnerAssistanceController
- **Location:** `RunnerAssistanceController::request()`
- **Concern:** XSS vulnerability if not escaped on display

---

## Recommendations

### 1. **Improve Real-Time Sync**

**Priority:** HIGH
- Reduce polling frequency to every 10 seconds as fallback
- Implement WebSocket connection health check
- Add exponential backoff for reconnection attempts
- Log WebSocket connection failures

### 2. **Add Timeout Mechanism**

**Priority:** HIGH
- Implement 5-minute timeout for pending requests
- Auto-decline after timeout
- Send notification to teller that runner is unavailable

### 3. **Database Cleanup**

**Priority:** MEDIUM
- Add scheduled job to delete old notifications (30+ days)
- Archive notifications to separate table
- Add indexed query on `user_id` and `is_read`

### 4. **Notification Deduplication**

**Priority:** MEDIUM
- Use database IDs instead of UUIDs for tracking
- Implement unique constraint on `(user_id, title, created_at)`
- Simplify merge logic

### 5. **Replace Cache Assignment**

**Priority:** HIGH
- Store runner assignments in database with explicit timeout
- Add status tracking: `pending_assignment` → `assigned` → `completed`
- Trigger automatic expiration via scheduled job

### 6. **Error Handling**

**Priority:** MEDIUM
- Add error callback to `ReverbManager.onError()`
- Implement graceful degradation when WebSocket unavailable
- Log errors for debugging

### 7. **Implement Soft Delete**

**Priority:** LOW
- Add `deleted_at` field to Notification model
- Use soft deletes to preserve audit trail
- Exclude soft-deleted from queries by default

### 8. **Add Request Expiry**

**Priority:** MEDIUM
- Add `expires_at` field to CashRequest
- Implement scheduled job to auto-expire pending requests
- Notify affected users

### 9. **Improve Message Formatting**

**Priority:** LOW
- Create `NotificationFormatter` class for consistent messages
- Support i18n (internationalization)
- Add message templates

### 10. **Add Notification Preferences**

**Priority:** LOW
- Let users choose which notifications to receive
- Add sound/vibration toggles in mobile app
- Implement notification categories (urgent, normal, low)

### 11. **Implement Notification History API**

**Priority:** MEDIUM
- Add pagination to notifications endpoint
- Support filtering by type/title
- Add search capability

### 12. **Add Request Analytics**

**Priority:** LOW
- Track request acceptance rates
- Monitor average response time
- Identify bottlenecks (which runners accept most)
- Generate reports for management

---

## Summary

The Sabong Betting System has a **functional but improvable** notification and request system:

### ✅ Strengths
- Real-time WebSocket communication via Reverb
- Database persistence for notifications
- Multiple notification types for different roles
- Request cooldown to prevent spam
- Comprehensive context data in notifications

### ⚠️ Weaknesses
- Over-reliance on polling over WebSocket
- No automatic request expiry
- Notifications accumulate indefinitely
- Cache-based assignment not persistent
- Limited error handling

### 🎯 Next Steps
1. Implement timeout mechanism for pending requests
2. Optimize notification polling/WebSocket balance
3. Add database cleanup jobs
4. Replace cache-based assignment with database solution
5. Improve error handling and logging

---

**Document Status:** Complete  
**Last Updated:** May 15, 2026  
**Reviewed By:** GitHub Copilot
