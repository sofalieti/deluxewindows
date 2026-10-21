# Developer Instructions: "Qualified Lead (3+ windows)" Conversion

**Site:** https://www.deluxewindows.com (Webflow, hard-coded Google tag; no Google Tag Manager)
**Google Ads account tag:** `AW-1030787786`
**New conversion action:** Qualified Lead (3+ windows)
**New conversion label:** `AW-1030787786/lc7YCMHvgp0dEMqlwusD`

---

## 1. Goal

Add a **window count** question to the lead form, and fire a **second** Google Ads conversion ("Qualified Lead") only when the visitor selects **3+ windows or "Not sure."**

- The existing **Confirmation Form** conversion (`AW-1030787786/Hs9eCP7MwngQyqXC6wM`, fired by `gtag_report_conversion()`) must keep firing exactly as today for **every** lead. **Do not change or remove it.**
- The new conversion fires **in addition to** it, for qualified leads only.

| Visitor selects | Confirmation Form (existing) | Qualified Lead (new) |
|---|---|---|
| 1 | ✅ fires | ❌ |
| 2 | ✅ fires | ❌ |
| 3–5 | ✅ fires | ✅ fires (value 1) |
| 6–10 | ✅ fires | ✅ fires (value 2) |
| 11+ | ✅ fires | ✅ fires (value 3) |
| Not sure | ✅ fires | ✅ fires (value 1) |

---

## 2. Form changes

Apply to **both** estimate forms:

- `#wf-form-Main-Form`
- `#wf-form-Mobile-Estimate-Modal`

(The contact/callback panel form doesn't need this field. Leave it as is.)

### New field

- **Type:** Select (dropdown), **required**
- **Label:** `How many windows are you looking to replace?`
- **Field name:** `window_count`
- **Options (label → value):**

| Label | Value |
|---|---|
| Select… (placeholder, not selectable) | `""` |
| 1 | `1` |
| 2 | `2` |
| 3–5 | `3-5` |
| 6–10 | `6-10` |
| 11+ | `11plus` |
| Not sure | `unsure` |

> Don't mention a minimum anywhere on the form. We still want every lead to come through.

### Pass the value through everywhere the lead goes

The custom lead script (the one containing `postLead`, `captureTracking`, `toPayload`, `postToGoogleBridges`, `sendGa4LeadConversion`) builds a `payload`. Add `window_count` to it so it reaches:

- the lead endpoint / CRM
- the **Google Sheet** (add a `window_count` column)
- the office's lead notification email
- the GA4 lead event, as a parameter (`window_count`)

---

## 3. Fire the new conversion

Inside the main lead form's submit handler, **after** the successful `postLead` response and the existing spam check, at the same spot where `gtag_report_conversion(...)` is called today, add:

```js
// --- Qualified Lead (3+ windows) — fires IN ADDITION to Confirmation Form ---
(function fireQualifiedLead(payload) {
  var QUALIFIED_VALUES = { '3-5': 1, '6-10': 2, '11plus': 3, 'unsure': 1 };
  var wc = (payload && payload.window_count) || '';
  if (!QUALIFIED_VALUES.hasOwnProperty(wc)) return;   // 1 or 2 → do nothing
  if (typeof window.gtag !== 'function') return;

  // Enhanced conversions: pass hashed-by-Google user data (email + phone)
  window.gtag('set', 'user_data', {
    email: payload.email || undefined,
    phone_number: payload.phone ? toE164(payload.phone) : undefined
  });

  window.gtag('event', 'conversion', {
    send_to: 'AW-1030787786/lc7YCMHvgp0dEMqlwusD',
    value: QUALIFIED_VALUES[wc],
    currency: 'USD',
    transaction_id: payload.lead_id || undefined   // dedupe if form double-submits
  });
})(payload);
```

Helper for US phone numbers, if one doesn't already exist:

```js
function toE164(raw) {
  var d = String(raw).replace(/\D/g, '');
  if (d.length === 10) return '+1' + d;
  if (d.length === 11 && d[0] === '1') return '+' + d;
  return undefined;
}
```

### Rules

1. **Order:** the existing `gtag_report_conversion(...)` call stays exactly where it is. Put the new block right after it.
2. **Only after a successful, non-spam submit.** Use the same guard the existing conversion uses (`isSpam` check).
3. **Use the field values (`payload.window_count`, `payload.email`, `payload.phone`).** Map them to whatever property names the payload actually uses.
4. `transaction_id`: if the lead endpoint returns a lead/record ID, pass it. If not, omit the line.
5. Don't add a new `<script async src="…gtag/js">` or a new `gtag('config', …)`. The Google tag is already loaded site-wide.

---

## 4. Also capture these click IDs (for later offline import)

The scripts already capture `gclid`, UTMs, `fbclid` and `msclkid`. Please also capture **`gbraid`** and **`wbraid`** (Google's iOS/app click IDs) the same way. Store them in the lead payload and the Google Sheet, with the same first-touch/persistence logic as `gclid`.

---

## 5. Testing checklist

1. Install the **Google Tag Assistant** Chrome extension, or open DevTools → Network and filter `googleadservices` or `pagead/conversion`.
2. Submit a test lead with **2 windows**:
   - ✅ one conversion request with label `Hs9eCP7MwngQyqXC6wM` (Confirmation Form)
   - ❌ **no** request with label `lc7YCMHvgp0dEMqlwusD`
3. Submit a test lead with **6–10 windows**:
   - ✅ request with `Hs9eCP7MwngQyqXC6wM`
   - ✅ request with `lc7YCMHvgp0dEMqlwusD` and `value=2`
4. Repeat step 3 on the **mobile estimate modal**.
5. Confirm `window_count` shows up in the Google Sheet row and the notification email.
6. Confirm the field is required and the form can't be submitted without a selection.
7. Mark test leads as **TEST** in the sheet so the office ignores them.

Within 24–48 hours of the first real qualified lead, the action's status in Google Ads (Goals → Conversions) should change from "Misconfigured/Unverified" to **"Recording conversions."**

---

## 6. What NOT to change

- Don't modify or remove `gtag_report_conversion` or the Confirmation Form label.
- Don't change the phone-click / contact-panel tracking.
- Don't change the base Google tag or GA4 config.

---

### Reference

| Item | Value |
|---|---|
| Google tag ID | `AW-1030787786` |
| GA4 ID | `G-JHYBB0THJM` |
| Existing conversion (Confirmation Form) | `AW-1030787786/Hs9eCP7MwngQyqXC6wM` |
| **New conversion (Qualified Lead)** | **`AW-1030787786/lc7YCMHvgp0dEMqlwusD`** |
| Qualifying values | `3-5`, `6-10`, `11plus`, `unsure` |
