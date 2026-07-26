# Course Registration System FAQ

This FAQ describes the most likely questions users and administrators will ask about the course registration system, and provides concise answers for each.

## Student / Applicant

- **How do I submit an application?**
  Register an account, complete the online application form, upload required documents, and submit the application for review.

- **Which programmes are open right now?**
  The landing page shows active programmes and the currently open intake. Available programmes are listed in the `Programmes` section.

- **How do I pay fees with M-Pesa STK Push?**
  Go to the student payments page, select `M-Pesa STK Push`, enter your phone number, and submit. The system sends a Daraja STK Push request to your mobile phone.

- **What if my M-Pesa payment fails?**
  The payment status will update to `Failed` and the gateway response is logged. You can retry the payment from the payment page.

- **How can I see my payment history and receipt?**
  The payments page lists all past payments, current status, and M-Pesa receipt numbers when available.

- **How do I upload my KCSE certificate and ID?**
  Use the student document upload page to submit scanned copies or photos of the required credentials.

- **How do I register for course units after admission?**
  After an application is approved, use the student registration flow to select units for the intake and semester.

- **Where can I see my timetable?**
  The timetable page displays scheduled classes for your registered units.

- **How can I access my academic results?**
  Results are available on the student results page once they are recorded by academic staff.

## Administrator / Registrar

- **How do I approve or reject student applications?**
  Use the admin applications review page to view details and submit approval or rejection decisions.

- **How do I set up intakes and deadlines?**
  Configure intakes from the admin intake management section, including application open and close dates.

- **How do I define programme fee structures?**
  Use the fee structure management page to create fees and assign them to programmes and intakes.

- **How do I verify uploaded documents?**
  Review documents in the admin document review area and mark them as approved or rejected.

- **How do I generate reports?**
  Use the admin reporting section to view summaries of applications, registrations, payments, and compliance metrics.

## Finance / Payments

- **How do I track M-Pesa STK Push payment status?**
  The system updates payment records based on Daraja webhook callbacks. Completed or failed payments are visible in the payment history.

- **How do I reconcile M-Pesa receipts?**
  Each payment record stores the M-Pesa receipt number and gateway response for reconciliation with Safaricom statements.

- **How are bank transfer payments handled?**
  Bank transfer payments are stored as a payment method with a unique reference for the payer to use when transferring funds.

- **What happens when an M-Pesa callback fails?**
  Failed webhook callbacks are logged, and the related payment remains in a pending or failed state until it is resolved.

- **What Daraja environment variables are required?**
  The system requires `MPESA_CONSUMER_KEY`, `MPESA_CONSUMER_SECRET`, `MPESA_SHORTCODE`, `MPESA_PASSKEY`, and callback URLs such as `MPESA_CALLBACK_URL`.

## Academic / Registration

- **How does the system enforce unit capacity and prerequisites?**
  The academic rules engine checks programme eligibility, prerequisite chains, and unit capacity limits during registration.

- **How are deadlines enforced?**
  Registration and add/drop deadlines are configured per intake/semester and enforced by the application logic.

- **How do I review student academic records?**
  Academic staff can view registered units, timetables, and results through the student-facing academic views.

## Technical / System

- **What MPesa webhook endpoints are exposed?**
  The webhook endpoints are available at `/api/mpesa/stk-callback`, `/api/mpesa/c2b-confirmation`, and `/api/mpesa/c2b-validation`.

- **How does the system match STK callbacks to payments?**
  It matches callbacks using `mpesa_checkout_request_id` stored on the payment record and updates the payment status accordingly.

- **Can the system support both M-Pesa and bank payments?**
  Yes, the system supports `M-Pesa STK Push`, `M-Pesa C2B`, and bank transfer payment methods.

- **Is there audit logging for document and payment actions?**
  Yes, the system records audit trails for document verification, payment processing, and administrative actions.

## Database / Storage

- **What database tables does the system use?**
  The core tables include `users`, `student_profiles`, `applications`, `fee_structures`, `payments`, `documents`, `registrations`, `timetable_entries`, `results`, `intakes`, and `programmes`.

- **How is payment data stored?**
  Payments store fields like `reference`, `student_profile_id`, `fee_structure_id`, `amount`, `currency`, `method`, `status`, `mpesa_receipt`, `mpesa_checkout_request_id`, `bank_reference`, `gateway_response`, and `paid_at`.

- **How are documents linked to students?**
  Uploaded documents are linked by `student_profile_id` and include metadata to track review status and audit history.

- **How is referential integrity enforced?**
  The system uses foreign keys between tables like payments-to-student profiles, applications-to-programmes, and registrations-to-intakes to keep data consistent.

- **How are audit trails captured?**
  Audit actions are recorded in related tables and logs when documents are reviewed, applications are approved, or payment statuses change.

- **What backup or retention rules should be used?**
  Use regular database backups and retain records according to policy, especially for student data, payment history, and document audits as required by Kenya Data Protection Act compliance.
