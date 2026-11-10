# Hospital case study PDF email

Oracle EBS Month-End Close Optimization for Hospitals |
Case Study | Exacore IT Solutions

How a Hospital Group Cut Its Oracle EBS Month-End Close
from 12 Days to 6
A slow close is usually a system problem, not a people problem. This case study
shows how an Oracle EBS health check found the bottlenecks behind one hospital
group&#39;s month-end close, and what fixed them.

 Client: Multi-specialty hospital group with several branches (anonymized)
 Platform: Oracle E-Business Suite R12.2
 Modules: GL, AP, AR, Fixed Assets, Inventory, Purchasing
 Integration: Hospital Information System (HIS) to Oracle EBS
 Service: Oracle EBS health check and performance tuning
 Result: Month-end close reduced from 12 working days to 6

The Challenge: Why the Close Was Slow Every day, the group&#39;s branches sent
thousands of billing entries from the HIS into Oracle EBS. These covered outpatient
and inpatient billing, pharmacy sales and diagnostics. Insurer claims, supplier
invoices and equipment additions arrived at the same time, and most of it landed in
the last week of the month.
Leadership assumed the finance team was slow and planned to add headcount. The
finance team suspected the system instead, and described four problems:
 HIS billing loads and accounting programs sat in &quot;Pending&quot; status for long
stretches
 Custom revenue and insurer-ageing reports ran for hours and were often
resubmitted
 HIS-to-AR and pharmacy stock-to-GL differences surfaced only in the final
days
 Nobody could say which delay was the real bottleneck

Our Approach: An Oracle EBS Health Check

We reviewed concurrent request history, database performance data and
reconciliation patterns across several closing cycles. The review focused on the
three areas most often behind a slow Oracle EBS close.
1. Concurrent Program Tuning
What we found: Interface loads, Auto Invoice, Create Accounting and Transfer to GL
shared the Standard Manager with reports and maintenance jobs. Statistics-
gathering and purge programs ran inside the close window. New HIS service codes
and departments had no matching EBS setup, so Auto Invoice failed repeatedly and
staff resubmitted it by hand.
What we fixed:
 Created dedicated concurrent manager queues and work shifts for close-
critical programs
 Moved maintenance jobs outside the close window
 Validated HIS service-code and department mapping before each load
 Added failure alerts so problems were visible in minutes, not hours
2. Custom Report Performance
What we found: The departmental revenue and insurer-ageing report took about 4
hours. It processed records row by row in PL/SQL, joined accounting tables without
selective filters and ran against stale optimizer statistics. The pharmacy stock
valuation report scanned all branches in one run.
What we fixed:
 Rewrote the logic as set-based SQL and added a supporting index
 Refreshed statistics on the affected tables
 Made reports branch-level so they run in parallel and off-peak
 Reduced the longest report from about 4 hours to about 25 minutes
3. Subledger-to-GL Reconciliation
What we found: Differences were discovered at the end of the close, when there was
the least time to investigate. Common causes were:
 Cancelled bills, refunds and credit notes posted late from the HIS
 Insurer claim adjustments and write-offs recorded in the final days
 Pharmacy and consumables inventory valuation not matching the GL
 Medical equipment placed in service but not capitalized on time
What we fixed:

 Introduced a daily HIS-to-AR control-total check
 Set up a weekly subledger-to-GL reconciliation with exception reports for
unaccounted and errored transactions
 Added an asset capitalization checkpoint to the close calendar
 Assigned a named owner for each module and branch
Results After Two Closing Cycles
 6 working days: month-end close time, down from 12
 25 minutes: longest custom report, down from about 4 hours
 Daily: HIS-to-AR reconciliation, replacing a last-minute scramble
 Days sooner: reconciliation differences found earlier in the cycle
Overtime during close dropped, management reports reached leadership sooner and
the headcount plan was shelved.
Frequently Asked Questions
Why is month-end close slow in Oracle EBS?
The usual causes are queued or failing concurrent programs, custom reports that run
for hours and subledger differences found late in the close.
What does an Oracle EBS health check include?
A review of concurrent processing, custom report performance and subledger-to-GL
reconciliation, ending in a prioritized list of fixes.
Does the review cover our Hospital Information System interface?
Yes. Mapping and timing issues between the HIS and Oracle EBS often delay the
close, so the interface is part of the review.
Is Your Month-End Close Taking Too Long?
Tell us about your close cycle. We will review concurrent programs, custom reports
and subledger reconciliation, then send you a clear list of what to fix first.