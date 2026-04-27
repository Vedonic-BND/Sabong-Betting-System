# Simplified Runner Assistance Notification System

## Overview
A clean, database-backed notification system for tellers to request assistance from runners with automatic 15-second assignment cooldown.

## How It Works

### 1. **Teller Sends Assistance Request**
- Teller selects request type: `assistance`, `need_cash`, `collect_cash`, or `other` (custom)
- Teller clicks "CALL RUNNER NOW"
- Backend creates a **Notification** record for EVERY runner
- Notification includes:
  - `title`: "Assistance Needed"
  - `message`: "{Teller Name} - {Request Type Message}"
  - `data`: JSON with `teller_id`, `teller_name`, `request_type`, `custom_message`
- All runners see notification in real-time (via periodic polling every 3 seconds)

### 2. **Runner Accepts Request**
- Runner sees notification: "Teller 1 - Cash needed at counter"
- Runner clicks "ACCEPT"
- Backend:
  - Checks if teller is already assigned (using Redis Cache)
  - If not assigned, assigns this runner for 15 seconds
  - Creates notification for **the teller**: "{Runner Name} is on the way"
  - Creates notification for **other runners**: "{Runner Name} has been assigned to {Teller Name}"
- Only this runner and teller can interact for 15 seconds
- After 15 seconds, assignment resets automatically

### 3. **Database Storage**
- All notifications saved to `notifications` table
- Includes notification history for audit trail
- Data stored as JSON in `data` column

## API Endpoints

### Teller
```
POST /api/assistance/request
{
  "request_type": "need_cash|collect_cash|assistance|other",
  "custom_message": "optional custom message"
}
Response: { "message": "Assistance request sent to all runners." }
```

### Runner
```
POST /api/assistance/accept/{teller_id}
Response: { "message": "Request accepted. You are assigned for 15 seconds.", "assigned_until": "..." }
```

## Message Types
- `assistance` → "Assistance needed at counter"
- `need_cash` → "Cash needed at counter"
- `collect_cash` → "Need to collect excess cash"
- `other` → "{custom_message}"

## Android Implementation

### RequestRunnerScreen (Teller)
- Shows 4 request type options with radio buttons
- Custom message input for "other" type
- Sends AssistanceRequest to `/api/assistance/request`

### RunnerScreen (Runner)
- Periodic notification refresh every 3 seconds
- Real-time incoming request card shows notification
- Accept button sends POST to `/api/assistance/accept/{teller_id}`
- Notification history shows all received notifications

## Key Features
✅ Database-backed - all notifications persisted
✅ Real-time notifications via polling
✅ 15-second assignment cooldown
✅ Automatic notification to other runners
✅ Clean, simple workflow
✅ No separate cash_requests table needed
✅ Built on existing Notification model
