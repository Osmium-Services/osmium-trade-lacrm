# Trade LACRM Sync

Fills the customer list of **Trade Accounts** from **Less Annoying CRM**. The LACRM service only connects and announces what happens in the CRM, and Trade Accounts only holds customers and lets them in. This service sits between them, so neither knows about the other, and Trade Accounts works the same with a different source or none.

Needs the Trade Accounts and Less Annoying CRM services.

## What it does

Choose *Less Annoying CRM* under Settings > Trade Accounts > Customer source. Then:

- a form submission becomes a pending application;
- a sale at the Won status approves the contact as an active customer; a sale at the Lost status suspends them;
- a contact update changes the details of a contact who is already a customer;
- *Import from Less Annoying CRM* on the customers page creates a customer for everyone at the Won status, and a second section there suspends everyone at the Lost status. Existing customers are left alone, so it can be run again.

Nothing is asked of LACRM unless it is the chosen source.

## How it works with the other services

It listens for LACRM's hooks (`lacrm.form.submitted`, `lacrm.sale.won`, `lacrm.sale.lost`, `lacrm.contact.updated`), asks LACRM questions through `lacrm.contact.get`, `lacrm.contacts.get_many`, `lacrm.contact.linked_people` and `lacrm.sales.won` / `lacrm.sales.lost`, and tells Trade Accounts what to do through its neutral events (`trade.application.received`, `trade.customer.approved`, `trade.customer.suspended`, `trade.customer.updated`). It reads customers through Trade Accounts' `trade.customer.lookup`, `trade.source.selected` and `trade.customer.check` hooks. It answers `trade.sources` with id `lacrm`, so existing customers keep their `lacrm` source and CRM contact id.

How LACRM fields map to customer columns (form labels such as `company name`, `main company phone`, `delivery address (if different to the above)`) was written for one site's form and is in `models/TradeLacrmMapper.php`. Check it against your own form.

## Install

Install it from the Store (or `install-service.php osmium-trade-lacrm`). It has no tables of its own. When moving a site from Trade Accounts 0.8 or earlier, install this service first, because those versions carried the CRM code themselves.
