# User Stories — Sports Betting Platform

## Roles

| Role | Description |
|---|---|
| **User** | Registered bettor who can browse events, place bets, manage wallet, and configure responsible gaming options. |
| **Manager** | Trusted operator who creates and manages sport events, sets outcomes and odds, and records results. |
| **Admin** | Platform administrator who manages user accounts and roles. |

---

## Epic 1 — Authentication & Account

### US-11 — Register

**As a** visitor,  
**I want to** create an account with my email, password, and birthdate,  
**So that** I can access the platform and place bets.

**Acceptance criteria:**
- Registration form requires: email, password, birthdate.
- Email must be unique; duplicate email shows a clear error.
- After successful registration, the user is redirected to the login page.

---

### US-12 — Log in

**As a** registered user,  
**I want to** log in with my email and password,  
**So that** I can access my account.

**Acceptance criteria:**
- Successful login redirects to the user dashboard.
- Invalid credentials show an error message.
- A suspended account cannot log in and sees a clear notice.
- A self-excluded account (active exclusion period) cannot log in and sees a clear notice.

---

### US-13 — Log out

**As an** authenticated user,  
**I want to** log out of my account,  
**So that** my session is terminated securely.

**Acceptance criteria:**
- Clicking "Log out" ends the session immediately.
- The user is redirected to the home page.

---

## Epic 2 — Wallet

### US-21 — Deposit funds

**As a** user,  
**I want to** deposit money into my wallet,  
**So that** I have a balance available to place bets.

**Acceptance criteria:**
- Deposit form accepts a positive decimal amount.
- The deposited amount is credited to the wallet balance immediately.
- A `DEPOT` transaction is recorded.
- The deposit is blocked if it would exceed the configured daily or weekly deposit limit.
- Amount ≤ 0 is rejected with a clear error.

---

### US-22 — View transaction history

**As a** user,  
**I want to** see a history of my wallet transactions (deposits, bets, winnings, refunds),  
**So that** I can track my spending and earnings.

**Acceptance criteria:**
- Transactions list shows: type, amount, date.
- Transaction types displayed: DEPOT, MISE, GAIN, REMBOURSEMENT.

---

## Epic 3 — Betting

### US-31 — Browse published events

**As a** user,  
**I want to** see all currently published sport events,  
**So that** I can choose which events to bet on.

**Acceptance criteria:**
- Only events with status `PUBLIE` are listed.
- Each event shows: name, sport, participants, date.

---

### US-32 — Place a bet

**As a** user,  
**I want to** select an outcome and enter a stake amount on a published event,  
**So that** I can participate in sports betting.

**Acceptance criteria:**
- The event must be in `PUBLIE` status; otherwise betting is blocked with an error.
- The event date must not have passed; otherwise betting is blocked.
- Amount must be greater than 0.
- The user must have sufficient wallet balance; otherwise an error is shown.
- On success: wallet is debited, a `MISE` transaction is recorded, the bet is created with status `EN_ATTENTE`, and the odds at the time of placing are locked to the bet.
- The potential gain (amount × odds) is shown in the success message.
- Odds are recalculated after each placed bet.
- The CSRF token is validated.

---

### US-33 — Bet limits enforcement

**As a** user who has configured betting limits,  
**I want** my bets to be rejected when I exceed my daily or weekly stake limit,  
**So that** I stay within the boundaries I set for myself.

**Acceptance criteria:**
- If the new bet would cause the total daily stake to exceed `betDaily`, the bet is rejected.
- If the new bet would cause the total weekly stake to exceed `betWeekly`, the bet is rejected.
- A clear error message specifies which limit was reached and its value.

---

### US-34 — View bet details

**As a** user,  
**I want to** view the details of one of my bets,  
**So that** I can see its status and potential payout.

**Acceptance criteria:**
- A user can only view their own bets (access denied otherwise).
- Detail page shows: event name, selected outcome, odds at time of bet, amount, potential gain, status, date.

---

### US-35 — View bet history

**As a** user,  
**I want to** browse a paginated list of all my past and active bets,  
**So that** I can review my betting activity.

**Acceptance criteria:**
- List is paginated (5 bets per page).
- Ordered by most recent first.
- Shows: event name, outcome, amount, odds, status, date.

---

## Epic 4 — Responsible Gaming

### US-41 — View responsible gaming settings

**As a** user,  
**I want to** access a dedicated page for responsible gaming,  
**So that** I can configure my protection limits and self-exclusion.

**Acceptance criteria:**
- Page shows all current limit values: daily/weekly deposit and bet limits.
- Any pending limit increases are displayed with their scheduled application date/time.
- Pending increases that have matured are applied automatically on page load.

---

### US-42 — Reduce a limit (immediate effect)

**As a** user,  
**I want to** lower one of my betting or deposit limits,  
**So that** the restriction takes effect immediately.

**Acceptance criteria:**
- Setting a limit to a value lower than the current one applies immediately.
- Setting a limit to "no limit" (empty) applies immediately.
- Any pending increase on that same field is cancelled.
- A success flash message is shown.

---

### US-43 — Increase a limit (deferred 48 hours)

**As a** user,  
**I want to** raise one of my betting or deposit limits,  
**So that** it takes effect after a 48-hour cooling-off period.

**Acceptance criteria:**
- Setting a limit to a value higher than the current one schedules a pending increase.
- The pending increase is stored with an `appliesAt` timestamp set to now + 48 hours.
- The increase is not applied until the timestamp is reached.
- A flash message informs the user that the change will be effective in 48 hours.

---

### US-44 — Self-exclusion

**As a** user,  
**I want to** exclude myself from the platform until a chosen date,  
**So that** I can take a break from betting.

**Acceptance criteria:**
- User picks an end date in the future.
- An active `SelfExclusion` record is created immediately.
- The user is logged out immediately after activation.
- Any login attempt during the exclusion period is blocked with a clear message.

---

## Epic 5 — Event Management (Manager)

### US-51 — Manager dashboard

**As a** manager,  
**I want to** see a summary of my events by status,  
**So that** I can quickly understand what requires my attention.

**Acceptance criteria:**
- Dashboard shows counts: total events, draft (`BROUILLON`), published (`PUBLIE`), closed (`FERME`).
- A list of the 5 most recent events is displayed.

---

### US-52 — Create a sport event

**As a** manager,  
**I want to** create a new sport event,  
**So that** I can set it up before opening it to bets.

**Acceptance criteria:**
- Form requires: name, sport, participants, event date.
- New events are created in `BROUILLON` status.
- The logged-in manager is automatically set as the owner.
- A success flash message is shown on creation.

---

### US-53 — Edit an event

**As a** manager,  
**I want to** edit an event I own,  
**So that** I can correct details before it goes live.

**Acceptance criteria:**
- Only the owning manager can edit the event (voter enforced).
- Only events in editable states can be modified.
- A success flash is shown after saving.

---

### US-54 — Delete an event

**As a** manager,  
**I want to** delete a draft event I own,  
**So that** I can remove events that will not take place.

**Acceptance criteria:**
- Only the owning manager can delete the event (voter enforced).
- Only events in a deletable state can be removed.
- The event and its outcomes are permanently deleted.
- CSRF token is validated.

---

### US-55 — Record the result

**As a** manager,  
**I want to** declare the winning outcome of a closed event,  
**So that** winning bets can be paid out.

**Acceptance criteria:**
- Action is only available when the event is in `FERME` status (voter enforced).
- Manager selects one outcome from the event's outcome list.
- The selected outcome is marked `isWinner = true`.
- Event status is set to `TERMINE`.
- CSRF token is validated.

---

### US-56 — Trigger payout

**As a** manager,  
**I want** winnings to be distributed automatically when I record a result,  
**So that** winning bettors receive their gains immediately.

**Acceptance criteria:**
- For each bet on the winning outcome: wallet is credited with `amount × oddsAtBet`, bet status is set to `GAGNE`, and a `GAIN` transaction is recorded.
- Bets on losing outcomes have their status set to `PERDU`.
- A success flash message confirms how many payouts were processed.

---

### US-57 — Publish an event

**As a** manager,  
**I want to** publish an event so users can start betting on it,  
**So that** the event goes live on the platform.

**Acceptance criteria:**
- Event must have at least one outcome; otherwise publishing is blocked with an error.
- Event status changes from `BROUILLON` to `PUBLIE`.
- Only the owning manager can publish (voter enforced).
- CSRF token is validated.

---

### US-58 — Close betting

**As a** manager,  
**I want to** close betting on a published event,  
**So that** no new bets are accepted while I wait for the result.

**Acceptance criteria:**
- Event status changes from `PUBLIE` to `FERME`.
- Only the owning manager can close (voter enforced).
- CSRF token is validated.
- After closing, the event is ready for result entry (US-55).

---

### US-59 — Cancel an event

**As a** manager,  
**I want to** cancel an event,  
**So that** all bettors are refunded and the event is closed.

**Acceptance criteria:**
- Event status is set to `ANNULE`.
- All `EN_ATTENTE` bets on this event are refunded: wallet is re-credited, bet status is set to `ANNULE`, a `REMBOURSEMENT` transaction is recorded.
- Only the owning manager can cancel (voter enforced).
- CSRF token is validated.

---

## Epic 6 — Administration

### US-61 — Assign / remove the Manager role

**As an** admin,  
**I want to** grant or revoke the Manager role on any user account,  
**So that** I control who can create and manage events.

**Acceptance criteria:**
- Toggling Manager on a user who does not have it adds `ROLE_MANAGER`.
- Toggling Manager on a user who already has it removes `ROLE_MANAGER`.
- Admin cannot modify their own roles.
- CSRF token is validated.
- A flash message confirms the change.

---

### US-62 — Suspend a user account

**As an** admin,  
**I want to** suspend a user account,  
**So that** the user can no longer log in.

**Acceptance criteria:**
- The account's `isActive` flag is set to `false`.
- The suspended user cannot log in until reactivated.
- Admin cannot suspend their own account.
- CSRF token is validated.
- A flash message confirms the suspension.

---

### US-63 — Reactivate a user account

**As an** admin,  
**I want to** reactivate a suspended user account,  
**So that** the user can log in again.

**Acceptance criteria:**
- The account's `isActive` flag is set to `true`.
- CSRF token is validated.
- A flash message confirms the reactivation.

---

### US-64 — Admin dashboard

**As an** admin,  
**I want to** see a global overview of platform activity,  
**So that** I can monitor the health of the platform.

**Acceptance criteria:**
- Dashboard displays: total users, active users, total events, published events, total bets, pending bets, won bets, total amount wagered.
- A table of the 20 most recent bets is shown.

---

### US-65 — List users

**As an** admin,  
**I want to** browse a paginated list of all registered users,  
**So that** I can find and manage specific accounts.

**Acceptance criteria:**
- User list is paginated (5 per page).
- Each row shows: email, roles, active status, wallet balance.
- Suspend and activate actions are accessible inline.
- Manager role toggle is accessible inline.
