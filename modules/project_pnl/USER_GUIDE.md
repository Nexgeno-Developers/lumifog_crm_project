# Project Profit & Loss — User Guide

*Last updated: 12 November 2026*

## Overview

The Profit & Loss dashboard shows, on every project, how much the project has earned, how much it has cost so far, and how much it is still expected to cost — without counting the same purchase twice.

**Where to find it:** open a project and stay on the **Overview** tab. The Profit & Loss section appears below the project details and expenses box.

**What it measures:**

- **Revenue** from sales invoices raised on the project.
- **Cost** from purchase orders (POs), vendor bills and expenses recorded on the project.

**The problem it solves:** POs are approved up front, but items are bought gradually as they are needed. When staff buy a PO item themselves and claim the money as an expense, the same item used to appear twice — once in the PO and once in expenses. Now each such expense is recorded **against its PO**, and the money is drawn from that PO's budget instead of being added on top.

## Setup and access

An administrator sets the module up once; after that, what each person sees depends on their permissions.

1. **Activate:** Setup → Modules → *Project Profit & Loss* → **Activate**.
2. **Upgrade Database:** when the module is updated, Setup → Modules shows **Upgrade Database** on it. Click it once; it adds the PO link to expenses and is safe to run again.
3. **Settings:** Setup → Modules → *Project Profit & Loss* → **Settings**. Choose the expense categories that must always be linked to a PO (for example *Material*). Admins only.
4. **Permissions:** grant **Project Profit & Loss → View** on the staff role or profile.

| Action | Who can do it |
| --- | --- |
| See the Profit & Loss section | Admins, and staff with *Project Profit & Loss → View* who can see the project |
| Choose "Against Purchase Order" on an expense | Staff who can create or edit expenses |
| Open **Link Expenses to POs** | Staff with *Expenses → Edit* and Profit & Loss view |
| **Close** / **Reopen** a PO for costing | Admins, and staff with *Purchase Orders → Edit* |
| Change Settings | Admins |

## Summary cards

Six cards at the top give the headline figures. The large number on each card is **before tax**; the **With tax** line under it shows the same figure including GST.

| Card | What it shows | Lines underneath |
| --- | --- | --- |
| **Revenue** (green edge) | Total of the project's sales invoices, excluding Draft and Cancelled | With tax · **Received** (payments recorded) · number of invoices |
| **Actual Cost** (red edge) | Money already spent: all project expenses plus vendor bills on the project's POs | With tax · **Against POs** (spent on PO items) · **Other expenses** (travel, food, labour and so on) |
| **Committed on POs** (amber edge) | Money still to be spent on open, approved POs: each PO's value minus what has been spent against it | With tax · "Unspent balance of open, approved POs" |
| **Expected Total Cost** (grey edge) | Actual Cost + Committed on POs — the full cost once every approved PO is bought | With tax · "Actual cost + committed on POs" |
| **Profit to Date** / **Loss to Date** | Revenue − Actual Cost. Blue when zero or positive, red with the title *Loss to Date* when negative | With tax · **Margin** (profit ÷ revenue; shows — when there is no revenue yet) |
| **Projected Profit** | Project value (the project's *Total Rate*) − Expected Total Cost. Shows — if the project has no value set | **Margin** (projected profit ÷ project value) · "Project value − expected total cost" |

Read *Profit to Date* as "where we stand today" and *Projected Profit* as "where we will land if the remaining POs are bought at their approved value". A project early in its life usually shows a loss to date and a healthy projected profit, because costs come before invoices.

## Notes and warnings

Short messages may appear between the cards and the progress bars; each only shows when it applies.

| Message | When it appears | What to do |
| --- | --- | --- |
| Yellow box: *"N expense(s) worth ₹X in PO-required categories are not linked to a purchase order and may be counted twice."* | Expenses in a category chosen in Settings have no PO selected | Click **Link them now** and pick the PO for each |
| Red line: *"Spending is over the approved PO value by ₹X in total"* | Money spent against one or more POs is more than their approved value | Check the PO table for the *Over budget* rows; raise or revise the PO if needed |
| Grey line: *"N PO(s) awaiting approval (₹X before tax) are not counted as committed."* | The project has POs not yet approved | Nothing — they are counted once approved |
| Grey line: *"Purchase module is not active"* | The Purchase module is switched off | POs are left out until it is active |

## Progress bars

Three bars show progress against a whole. Each shows the percentage on the right and "amount / total" underneath.

| Bar | Compares | Read it as |
| --- | --- | --- |
| **Invoiced vs project value** (green) | Revenue before tax ÷ project value | How much of the contract has been billed |
| **Expected cost vs project value** (amber) | Expected Total Cost ÷ project value | How much of the contract value will be eaten by cost; under 100% means the project is expected to make money |
| **Received vs invoiced** (blue) | Payments received ÷ invoiced amount with tax | How much of what was billed has been collected |

The first two bars only appear when the project has a value (*Total Rate*) set.

## Monthly Revenue vs Cost chart

The chart shows, month by month, what came in and what went out, with a line for the running profit.

| Legend item | Drawn as | Meaning |
| --- | --- | --- |
| **Revenue** | Green bar | Invoices dated in that month (before tax) |
| **Against POs** | Amber bar | Expenses linked to a PO, plus vendor bills, dated in that month |
| **Other expenses** | Red bar | Expenses not linked to any PO |
| **Cumulative Profit** | Blue line | Running total of revenue minus spending up to that month |

- **Axis units:** K = thousand, L = lakh, Cr = crore, in the project's currency (for example ₹15.0 L = ₹15,00,000).
- **Hover** over a month to see the exact amount of each item.
- **Click a legend item** to hide or show it.
- Months with nothing recorded show as zero, so gaps in activity are visible. Committed (not yet spent) PO money is not on this chart — it shows only what has actually happened.
- The chart covers up to the last 24 months of activity.

## Purchase Order Usage table

One row per purchase order on the project, showing how much of each PO's budget has been used.

| Column | Meaning |
| --- | --- |
| **Number** | PO number (opens the PO) and its order date |
| **Vendor** | Supplier on the PO |
| **PO Value** | Approved value before tax |
| **Spent Against PO** | Linked expenses + vendor bills for this PO, with a bar and percentage used |
| **Remaining** | PO Value − Spent, never below zero; shows *Over by ₹X* in red when exceeded |
| **Status** | One of the tags below |
| *(button)* | **Close** or **Reopen** |

**Bar colours:** green under 90% used, amber at 90% or more, red when over budget.

| Status tag | Meaning | Counted as committed? |
| --- | --- | --- |
| **Open** (green) | Approved and still being bought against | Yes, the Remaining amount |
| **Over budget** (red) | Approved, and more has been spent than its value | No — the overspend is already in Actual Cost |
| **Awaiting approval** (grey, row greyed out) | Not approved yet | No |
| **Closed** (blue) | Marked finished for costing | No |

**Close** tells the dashboard that nothing more will be bought on this PO, so its unspent balance stops counting as committed cost (for example when the remaining items are no longer needed). A confirmation asks before closing. **Reopen** undoes it. Closing affects only the P&L calculation — it does not change the PO itself in the Purchase module. Only approved POs have the button.

## Sales Invoices and Expenses by Category

**Sales Invoices** lists the invoices counted as revenue: **Number** (opens the invoice, with its status tag such as Unpaid, Partially Paid or Paid), **Date**, and **Before Tax** amount. Draft and Cancelled invoices are not listed.

**Expenses by Category** groups all project expenses by category:

| Column | Meaning |
| --- | --- |
| **Category** | Expense category, with the number of expenses in brackets |
| **Before Tax** | Total of that category |
| **Against PO** | Part of that total linked to a PO. If a material-type category shows ₹0.00 here, its expenses have not been linked yet |
| **Share** | That category's share of all expenses |

## Recording an expense against a PO

When staff buy an item that is listed in a PO, they record the expense as usual and pick that PO in **Against Purchase Order**.

**Where the field appears:**

- **Expenses → New Expense** (or editing one): under the **Project** field, once a project is chosen.
- **Project → Expenses tab → Record Expense**: under **Expense Category**.

The field only appears when the project has purchase orders. Each option reads like *"#LF/PO/26-27/-00544 — Asawa Insulation (₹19,55,340.00, ₹11,72,835.00 used)"*: PO number, vendor, PO value and the amount already spent against it. POs not yet approved are marked *Awaiting approval*; closed ones *Closed*.

| Choice | Use it for |
| --- | --- |
| A PO | The money was spent on an item from that PO. It is drawn from the PO's budget, not added on top |
| **— Not against a PO —** | Travel, food, pocket money, labour and anything else not listed in a PO |

**Required categories:** for categories chosen in Settings (such as *Material*), a red star shows next to the field and the expense cannot be saved without a PO. The message *"Please select the purchase order this expense was for."* appears under the field.

Only the project's own POs can be chosen; the system rejects a PO from another project even if one is sent.

## Link Expenses to POs screen

Use this screen to link expenses that were recorded before the PO field existed, or that were saved without a PO.

1. On the project's Profit & Loss section, click **Link Expenses to POs** (top right), or **Link them now** in the yellow warning.
2. Every expense on the project is listed: **Date**, **Category**, **Name / Note** (the name opens the expense), **Before Tax** amount, and an **Against Purchase Order** dropdown.
3. Rows highlighted in yellow with a **Needs PO** tag are in a PO-required category and not linked yet.
4. Pick the PO for each expense that was spent on a PO item; leave others as *Not against a PO*.
5. Click **Submit**. A message confirms how many expenses were updated, and the dashboard recalculates straight away.

**Back to project** returns to the project page.

## How the figures are calculated

Every PO item is counted once: money spent on it is taken out of the PO's remaining budget, not added to it.

| Figure | Calculation |
| --- | --- |
| Revenue | Sum of project invoices, excluding Draft and Cancelled |
| Actual Cost | All project expenses + vendor bills on the project's POs |
| Spent against a PO | Expenses linked to that PO + its vendor bills |
| Committed on a PO | PO value − spent against it, never below zero; zero if the PO is closed or not approved |
| Over budget on a PO | Spent against it − PO value, when positive |
| Expected Total Cost | Actual Cost + total committed |
| Profit to Date | Revenue − Actual Cost |
| Projected Profit | Project value − Expected Total Cost |

**Tax:** main figures exclude GST (invoice and PO tax are taken off; expenses use their pre-tax amount). The *With tax* lines add it back. Expense tax is worked out the same way as the project's existing Expenses box, so the two always agree.

**Left out on purpose:** Draft and Cancelled invoices; POs awaiting approval (until approved); POs turned into an expense with the Purchase module's **Convert** button (that expense is already in Actual Cost).

**Worked example — one approved PO worth ₹19,55,340 (before tax), and ₹26,00,137 of expenses on the project:**

| Situation | Actual Cost | Committed | Expected Total Cost |
| --- | --- | --- | --- |
| Old way: PO and expenses simply added | — | — | ₹45,55,477 (PO items bought by staff counted twice) |
| ₹11,72,835 of material expenses linked to the PO | ₹26,00,137 | ₹7,82,505 | **₹33,82,642** |
| Same, and the PO then closed | ₹26,00,137 | ₹0 | ₹26,00,137 |
| ₹21,42,140 linked (more than the PO value) | ₹26,00,137 | ₹0 | ₹26,00,137, PO shown *Over by ₹1,86,800* |

## Rules for staff and FAQ

Four rules keep the figures right:

1. Anything listed in a PO is always recorded **against that PO** — whether staff buy it or the vendor bills for it.
2. **Not against a PO** is only for costs not in any PO: travel, food, pocket money, labour, site expenses.
3. Do **not** use the **Convert** button on the Purchase Orders list. It turns the whole PO into one expense in one go and cannot handle items bought bit by bit.
4. When a PO is finished and nothing more will be bought on it, **Close** it on the project's P&L.

### FAQ

**What does the Purchase module's "Convert" (to expense) button do?** It creates a single expense for the PO's full total and marks the PO as converted. It works once per PO and has no partial option. The dashboard skips converted POs so they are not counted twice, but use the PO field on expenses instead.

**An item cost more than the PO line.** Record the actual amount against the PO. The PO shows *Over budget* with the extra amount, and the extra is included in Actual Cost.

**Staff bought only part of a PO.** That is the normal case. Each purchase is recorded against the PO as it happens; the PO's Remaining amount goes down each time.

**Why is Revenue ₹0?** No sales invoices (other than Draft or Cancelled) have been raised on the project yet. Profit to Date will be a loss until invoicing starts; check Projected Profit instead.

**Why don't pending POs count?** A PO awaiting approval may still change or be rejected. Its value is shown in the grey note and counts once approved.

**I linked an expense to the wrong PO.** Edit the expense and change the field, or change it on the **Link Expenses to POs** screen.

**Can an expense be linked to a specific item inside a PO?** Not at present; links are per PO. Item-level tracking can be added if needed.
