# Quote Auto Followups Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-09-30

Initial version

### Added

- Automatic follow-up emails of validated quotes, with the quote PDF attached, sent to the quote's "Customer contact follow-up" contacts by an hourly scheduled job
- Module setup: enable/disable, maximum number of follow-ups, list of delays, start date, maximum emails per run, sending hours and working days, sender address, email template (native Dolibarr templates), blind carbon copy
- Default "Quote follow-up" email template created on activation
- Quote extrafields: "Do not follow up", "Sent to customer on", "Nb follow-ups", "Last follow-up", "Last follow-up error"
- "Follow up by email" button and "Next follow-up" information on the quote card
- Agenda events for every follow-up sent or failed, linked to the quote and its third party
- Permissions to see follow-up information, send follow-ups manually and change follow-up settings of a quote
