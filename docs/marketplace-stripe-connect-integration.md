Option 1: OMS-Driven Payouts (Per Item)
---------------------------------------

Each order item triggers a payout when it transitions to a "delivered" (or post-return-period) state in the OMS state machine.

### Pros

-   **Real-time**: Merchants get paid quickly, improving satisfaction
-   **Native to Spryker**: Leverages the OMS state machine, which is the idiomatic Spryker way to handle order lifecycle events
-   **Granular control**: Each item has its own payout state (paid, failed, retried), fully traceable
-   **Simpler reconciliation**: 1 item → 1 transfer/payout --- easy to map

### Cons

-   **High API volume**: Every item triggers a Stripe API call --- can hit rate limits at scale
-   **Stripe fees**: Individual transfers may incur more cost than batched ones (depends on your Stripe pricing)
-   **Fragmented merchant experience**: Merchants receive many small payouts instead of consolidated statements
-   **Error handling complexity**: Each OMS transition must handle Stripe failures, retries, and idempotency individually

* * * * *

Option 2: Scheduled Batch Payouts (Cron)
----------------------------------------

A scheduled job (e.g., weekly/monthly) collects all delivered items per merchant and issues a single aggregated payout.

### Pros

-   **Fewer API calls**: One transfer per merchant per cycle --- efficient and rate-limit-friendly
-   **Cleaner merchant experience**: Merchants receive a single consolidated payout with a clear statement
-   **Better for accounting**: Aligns with typical B2B invoicing/settlement cycles
-   **Return buffer**: Natural window to deduct returns/refunds before payout
-   **Lower Stripe costs**: Fewer individual transfer operations

### Cons

-   **Delayed payment**: Merchants wait longer --- may be a friction point
-   **Separate infrastructure**: Requires a dedicated console command / cron job outside the OMS
-   **Reconciliation needs work**: You must build the logic to aggregate items, track what's been paid, and handle partial failures
-   **OMS coupling is looser**: The OMS must still mark items as "ready for payout" but doesn't control the actual transfer

* * * * *

**Option 3: Hybrid Approach**
-----------------------------------


```text
OMS State Machine                    Cron Job
─────────────────                    ────────
item.delivered                       Runs weekly/monthly
    │                                    │
    ▼                                    ▼
mark item as                     Query all items in
"ready_for_payout"               "ready_for_payout" state
(no Stripe call)                 per merchant
                                     │
                                     ▼
                                 Aggregate amounts,
                                 deduct returns/fees
                                     │
                                     ▼
                                 Stripe Transfer per merchant
                                 (via Connect)
                                     │
                                     ▼
                                 Update OMS items →
                                 "payout_completed"
```

### Pros:

1.  **OMS stays in control** of item lifecycle --- each item has a clear state (ready_for_payout → payout_completed → payout_failed)
2.  **Cron handles the actual Stripe call** --- batched, efficient, retry-friendly
3.  **Return window** is naturally built in --- you can exclude items that were returned before the payout cycle
4.  **Stripe Connect best practice** --- use Transfer objects to the connected account, aggregated per settlement period
5.  **Aligns with B2B expectations** --- mid-to-large enterprises expect periodic settlement, not per-item drip payments
