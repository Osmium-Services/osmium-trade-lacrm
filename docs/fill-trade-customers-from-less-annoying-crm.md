---
title: Fill trade customers from Less Annoying CRM
tags: trade, lacrm, crm, import, customers, won, lost, sync
page: admin/trade/lacrm-import
---

**Trade LACRM Sync** keeps your trade customers in step with Less Annoying CRM.

- A submitted application form creates a **pending** trade customer.
- A **won** sale makes the customer **active**.
- A **lost** sale **suspends** the customer, so they can no longer sign in.

## Switch it on

In **Trade Accounts** settings, choose Less Annoying CRM as the **Customer source**. The Less Annoying CRM settings define which pipeline statuses mean won and lost.

## Catch up on existing sales

Use the import page to catch up on sales from before the sync was running:

- **Check for new customers**, then **Import them**: everyone at the won status becomes an active trade customer. People who are already customers are left alone, so it is safe to run again.
- **Check lost sales**, then **Suspend them**: customers at the lost status are suspended. Closed accounts are left closed.
