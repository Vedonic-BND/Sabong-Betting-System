# WebSocket Diagnostic Guide - Reverb Running ✅

## Current Status
✅ **Reverb server is running** on port 8080
✅ **Active connections exist** (shown in netstat output)

But browser console shows: "WebSocket not available"

---

## Step-by-Step Diagnosis

### Step 1: Check Browser Console

Refresh the financial overview page and look for these logs:

**If you see:**
```
✅ [Financial Overview] WebSocket listener active and ready
```
→ Everything works! Real-time updates are live ✅

**If you see:**
```
⚠️ [Financial Overview] Echo not available - window.Echo is undefined
```
→ Need to diagnose further (see Step 2)

**If you see:**
```
❌ [Financial Overview] Error setting up WebSocket listener: ...
```
→ Echo exists but has an error (see Step 3)

---

### Step 2: Check .env Configuration

Run this to verify:
```bash
grep "BROADCAST_CONNECTION\|REVERB_HOST\|REVERB_PORT\|REVERB_SCHEME" .env
```

Should show:
```
BROADCAST_CONNECTION=reverb
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
VITE_REVERB_HOST=127.0.0.1
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

**If any are different:**
```bash
# Edit .env and update to match above
nano .env

# Then restart Reverb:
php artisan reverb:start --host=0.0.0.0 --port=8080
```

---

### Step 3: Check if echo.js Loads

In browser DevTools Console, run:
```javascript
console.log('Echo defined:', typeof window.Echo);
console.log('Echo object:', window.Echo);
```

**If undefined:**
- Vite might not have compiled
- Try rebuilding:
```bash
npm run build
```

---

### Step 4: Check Browser Network Tab

1. Open DevTools → Network tab
2. Refresh financial overview
3. Look for WebSocket connections
4. Filter by "ws:" or "8080"

**Should see:**
```
ws://127.0.0.1:8080/app/<key>
Status: 101 (Switching Protocols)
```

**If you don't see it:**
- Reverb not responding
- Connection blocked
- Wrong port/host

---

### Step 5: Check Reverb Server Output

Look at the terminal where you ran:
```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

**Should show:**
```
INFO  Starting server on 0.0.0.0:8080 (127.0.0.1).
```

**Watch for connection logs when you refresh page:**
```
[HH:MM:SS] New connection: ...
[HH:MM:SS] Channel subscribed: cash-status
```

---

## Common Issues & Fixes

### Issue: `BROADCAST_CONNECTION=null`

**Problem:** Broadcasting is disabled

**Fix:**
```bash
# Edit .env
BROADCAST_CONNECTION=reverb

# Restart Laravel (if using dev server)
php artisan serve
```

---

### Issue: Vite not compiled

**Problem:** echo.js not bundled into app.js

**Fix:**
```bash
npm run dev
# OR
npm run build
```

---

### Issue: CORS/Connection Refused

**Problem:** Browser can't reach Reverb

**Possible causes:**
- Firewall blocking 8080
- Reverb not started
- Wrong host/port in .env

**Fix:**
```bash
# Verify Reverb is listening:
netstat -an | grep 8080
# Should show: 0.0.0.0:8080 LISTENING

# Test local connection:
curl http://127.0.0.1:8080
```

---

### Issue: `echo.js` error in browser console

**Problem:** Echo initialization failed

**Check:**
1. Is REVERB_HOST correct for your setup?
2. Is Reverb actually running?
3. Are REVERB_PORT and REVERB_SCHEME correct?

---

## Quick Verification Checklist

```bash
# 1. Is Reverb running?
ps aux | grep reverb
# Should show the running process

# 2. Is port 8080 listening?
netstat -an | grep 8080
# Should show: 0.0.0.0:8080 LISTENING

# 3. Is .env configured?
grep BROADCAST_CONNECTION .env
# Should show: BROADCAST_CONNECTION=reverb

# 4. Is npm built?
ls public/build/manifest.json
# Should exist and be recent

# 5. Test WebSocket directly
npm install -g wscat
wscat -c ws://127.0.0.1:8080/app/lt6ejfvgbim9vntnqxms
# Should connect
```

---

## What Should Happen

### With Everything Working ✅

```
1. Page loads
2. echo.js initializes
3. Window.Echo is created
4. Financial Overview script runs
5. Connects to channel 'cash-status'
6. Browser console shows: ✅ WebSocket listener active
7. User places a bet
8. Server broadcasts event
9. Browser receives event immediately
10. Page reloads in real-time
```

### With Fallback Active ⚠️

```
1. Page loads
2. Echo fails to initialize (Reverb down)
3. Browser console shows: ⏱️ Fallback enabled
4. 30-second auto-reload activates
5. Page updates every 30 seconds
6. Everything still works, just delayed
```

---

## Current Behavior (After Latest Fix)

With the new delayed initialization:

1. Page loads
2. Waits 1 second for Echo
3. After 1 second:
   - If Echo exists → Sets up WebSocket listener
   - If Echo missing → Shows detailed why
4. Sets up fallback if needed

This gives better diagnostics and more reliable initialization.

---

## Next Actions

**Check your browser console right now:**

Open financial overview page and note the logs. Reply with:

1. What console message appears first?
2. What console message appears after 1 second?
3. Do you see "✅ WebSocket listener active" or something else?

This will help identify exactly what's happening! 🔍
