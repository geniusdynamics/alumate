# Requirements Document: PayRails.Core Platform

## Introduction

PayRails.Core is a unified African financial middleware aggregation platform designed to serve as the "Financial Operating System" for Africa. The platform acts as middleware infrastructure that connects multiple payment gateways (M-Pesa, Airtel Money, Equity Bank, SasaPay) through a unified API, providing aggregation, orchestration, consolidation, and Wallet-as-a-Service (WaaS) capabilities.

The system implements a B2B2B model with three distinct user levels: Super Admins (PayRails internal staff), Integrators (developers/resellers who manage multiple merchants), and Merchants (end-users such as schools, retailers, and SMEs). The platform features a shadow ledger for real-time reconciliation, smart routing with failover logic, and built-in compliance tools including eTIMS tax integration and KYC/KYB workflows.

## Glossary

- **PayRails_System**: The complete middleware platform including all services, APIs, and user interfaces
- **Super_Admin**: PayRails internal staff with system-wide access and control
- **Integrator**: Developer or reseller who manages multiple merchants and earns revenue share
- **Merchant**: End-user client (schools, retailers, SMEs) who receives and manages funds
- **Shadow_Ledger**: Internal ACID-compliant double-entry ledger that mirrors external provider states
- **Payment_Provider**: External payment gateway (M-Pesa, Airtel Money, Equity Bank, SasaPay)
- **WaaS**: Wallet-as-a-Service functionality backed by SasaPay
- **IPN**: Instant Payment Notification (webhook from payment providers)
- **Gateway_Adapter**: Interface implementation for specific payment providers
- **Reconciliation_Engine**: Background service that compares provider transactions against internal ledger
- **eTIMS**: Electronic Tax Invoice Management System (KRA compliance for Kenya)
- **Tax_System**: Generic term for electronic tax systems across different markets (eTIMS-Kenya, RRA-Rwanda, TRA-Tanzania, OBR-Burundi, FIRS-Nigeria)
- **KYB**: Know Your Business (compliance verification for merchants)
- **KYC**: Know Your Customer (compliance verification for individuals)
- **CRB**: Credit Reference Bureau (credit checking services like Metropol)
- **App_Configuration**: Specific gateway setup with unique credentials (e.g., separate M-Pesa paybills or tills)
- **Monthly_Platform_Fee**: Recurring subscription fee charged to merchants, from which Integrators earn revenue share
- **STK_Push**: SIM Toolkit Push for initiating M-Pesa payments from customer phones
- **Withdrawal_Request**: Merchant-initiated request to transfer funds from wallet to external gateway
- **Maker_Checker**: Two-person approval workflow for financial transactions
- **DLQ**: Dead Letter Queue for failed webhook processing
- **COGS**: Cost of Goods Sold (gateway fees paid to providers)
- **Liquidity_Monitor**: System that tracks available funds across all wallets and gateways
- **Spend_Rules_Engine**: Configurable rules for wallet spending limits and approvals
- **Revenue_Share**: Commission earned by Integrators on merchant transactions
- **Failover_Logic**: Automatic routing to backup gateway when primary fails
- **Fiscal_Receipt**: Tax-compliant receipt generated via eTIMS integration

## Requirements

### Requirement 1: User Authentication and Authorization

**User Story:** As a user of any level (Super Admin, Integrator, or Merchant), I want to securely authenticate and access only the features appropriate to my role, so that the system maintains proper security and access control.

#### Acceptance Criteria

1. WHEN a user attempts to log in, THE PayRails_System SHALL authenticate credentials using OAuth2 protocol
2. WHEN authentication succeeds, THE PayRails_System SHALL issue access tokens with role-based scopes
3. WHEN a user with payment:approve scope attempts to perform an action, THE PayRails_System SHALL require 2FA verification
4. WHEN a user attempts to access a resource, THE PayRails_System SHALL validate the user has the required OAuth2 scope
5. WHEN an access token expires, THE PayRails_System SHALL require re-authentication
6. WHEN a user's role changes, THE PayRails_System SHALL update their OAuth2 scopes immediately
7. IF an authentication attempt fails three times, THEN THE PayRails_System SHALL temporarily lock the account and notify administrators

### Requirement 2: Integrator Client Management

**User Story:** As an Integrator, I want to onboard and manage multiple merchant clients through a single portal, so that I can scale my business efficiently.

#### Acceptance Criteria

1. WHEN an Integrator creates a new merchant, THE PayRails_System SHALL generate a unique merchant_id
2. WHEN a merchant is created, THE PayRails_System SHALL automatically create a SasaPay wallet for that merchant
3. WHEN an Integrator views their client list, THE PayRails_System SHALL display all merchants with their status and key metrics
4. WHEN an Integrator configures payment gateways for a merchant, THE PayRails_System SHALL store gateway credentials in OpenBao
5. WHEN an Integrator enables a payment channel, THE PayRails_System SHALL validate the configuration before activation
6. WHEN a merchant is created, THE PayRails_System SHALL generate API credentials (Client ID and Secret)
7. WHEN an Integrator sets a revenue share markup, THE PayRails_System SHALL validate the markup is within allowed limits

### Requirement 3: Payment Gateway Aggregation

**User Story:** As a Merchant, I want to receive payments from multiple channels (M-Pesa, Airtel Money, Equity Bank) through a single integration, so that I don't need to manage multiple gateway integrations.

#### Acceptance Criteria

1. WHEN a payment is initiated through any gateway, THE PayRails_System SHALL route it through the appropriate Gateway_Adapter
2. WHEN a Payment_Provider sends an IPN, THE PayRails_System SHALL normalize the webhook data into a standard format
3. WHEN a payment is received, THE PayRails_System SHALL record entries in the Shadow_Ledger
4. WHEN a payment completes, THE PayRails_System SHALL notify the merchant via configured webhook
5. WHEN multiple gateways are configured, THE PayRails_System SHALL apply routing rules to select the appropriate gateway
6. WHEN a gateway fails, THE PayRails_System SHALL execute Failover_Logic to route to backup gateway
7. WHEN a payment is processed, THE PayRails_System SHALL calculate and record gateway_fee, platform_fee, and integrator_fee

### Requirement 4: Shadow Ledger and Reconciliation

**User Story:** As a Super Admin, I want the system to maintain an internal ledger that reconciles against external providers in real-time, so that I can detect discrepancies and ensure financial accuracy.

#### Acceptance Criteria

1. WHEN a payment transaction occurs, THE Shadow_Ledger SHALL create double-entry ledger entries
2. WHEN the Reconciliation_Engine runs, THE Shadow_Ledger SHALL compare internal balances against Payment_Provider API balances
3. IF a discrepancy is detected, THEN THE PayRails_System SHALL create an alert for Super_Admin review
4. WHEN an IPN is missed, THE Reconciliation_Engine SHALL detect the missing transaction and create a reconciliation entry
5. WHEN a reconciliation completes, THE PayRails_System SHALL log the sync status in gateway_sync_logs table
6. THE Shadow_Ledger SHALL maintain ACID compliance for all transaction entries
7. WHEN system balance differs from API balance, THE Liquidity_Monitor SHALL flag the discrepancy immediately

### Requirement 5: Merchant Income Consolidation

**User Story:** As a Merchant, I want to view all my incoming payments from different channels in one unified dashboard, so that I can easily track and reconcile my income.

#### Acceptance Criteria

1. WHEN a Merchant views their income dashboard, THE PayRails_System SHALL display transactions from all configured payment channels
2. WHEN a Merchant applies filters, THE PayRails_System SHALL filter transactions by date range, channel, amount, or status
3. WHEN a Merchant tags a transaction, THE PayRails_System SHALL store the tag and allow filtering by tags
4. WHEN an unidentified payment is received, THE PayRails_System SHALL place it in the unidentified payments bin
5. WHEN a Merchant allocates an unidentified payment, THE PayRails_System SHALL move it to the appropriate category
6. WHEN a Merchant views analytics, THE PayRails_System SHALL display income breakdown by channel and gateway
7. WHEN a Merchant exports transactions, THE PayRails_System SHALL generate a CSV or Excel file with all transaction details

### Requirement 6: Wallet-as-a-Service (WaaS)

**User Story:** As a Merchant, I want to create and manage virtual wallets for different departments or purposes, so that I can control spending and track expenses by category.

#### Acceptance Criteria

1. WHEN a Merchant creates a wallet, THE PayRails_System SHALL create a virtual wallet backed by SasaPay
2. WHEN a Merchant creates sub-wallets, THE PayRails_System SHALL establish a hierarchy with the master wallet
3. WHEN a Merchant sets spending limits, THE Spend_Rules_Engine SHALL enforce the limits on all transactions
4. WHEN a wallet balance is queried, THE PayRails_System SHALL return the current balance from SasaPay
5. WHEN funds are transferred between wallets, THE Shadow_Ledger SHALL record the internal transfer
6. WHEN a wallet transaction occurs, THE PayRails_System SHALL update both the Shadow_Ledger and SasaPay balance
7. WHEN a Merchant views wallet history, THE PayRails_System SHALL display all transactions with timestamps and descriptions

### Requirement 7: Maker-Checker Workflow for Payments

**User Story:** As a Merchant with financial controls, I want payment requests to require approval from a second person, so that I can prevent unauthorized or fraudulent transactions.

#### Acceptance Criteria

1. WHEN a user with payment:create scope initiates a payment, THE PayRails_System SHALL create a pending payment request
2. WHEN a payment request is created, THE PayRails_System SHALL notify users with payment:approve scope
3. WHEN a user with payment:approve scope reviews a request, THE PayRails_System SHALL display full payment details
4. WHEN an approver approves a payment, THE PayRails_System SHALL execute the payment through the appropriate gateway
5. WHEN an approver rejects a payment, THE PayRails_System SHALL mark it as rejected and notify the requester
6. IF the requester and approver are the same person, THEN THE PayRails_System SHALL reject the approval attempt
7. WHEN a payment requires 2FA, THE PayRails_System SHALL verify the 2FA code before processing

### Requirement 8: Multi-Market Tax System Integration

**User Story:** As a Merchant, I want automatic tax receipts generated for my transactions in my operating country, so that I remain compliant with local tax authorities without manual effort.

#### Acceptance Criteria

1. WHEN a payment is completed, THE PayRails_System SHALL send transaction details to the configured Tax_System
2. WHEN the Tax_System responds, THE PayRails_System SHALL store the Fiscal_Receipt with the transaction record
3. WHEN a Merchant views a transaction, THE PayRails_System SHALL display the associated Fiscal_Receipt
4. WHEN a Tax_System is unavailable, THE PayRails_System SHALL queue the receipt generation request
5. WHEN the tax queue is processed, THE PayRails_System SHALL retry failed receipt generations
6. WHEN a Merchant exports transactions, THE PayRails_System SHALL include Fiscal_Receipt numbers in the export
7. IF Tax_System receipt generation fails after retries, THEN THE PayRails_System SHALL alert the Merchant

### Requirement 9: Super Admin System Monitoring

**User Story:** As a Super Admin, I want comprehensive visibility into system health, revenue, and gateway performance, so that I can proactively manage the platform.

#### Acceptance Criteria

1. WHEN a Super_Admin views the dashboard, THE PayRails_System SHALL display gross transaction volume for the current period
2. WHEN a Super_Admin views revenue metrics, THE PayRails_System SHALL display COGS, integrator payouts, and net platform revenue
3. WHEN a Super_Admin views gateway health, THE PayRails_System SHALL display latency, success rate, and error rate for each Payment_Provider
4. WHEN a Super_Admin views the DLQ, THE PayRails_System SHALL display all failed webhook processing attempts
5. WHEN a Super_Admin views liquidity, THE Liquidity_Monitor SHALL display system balance versus API balance for all gateways
6. WHEN a Super_Admin configures routing rules, THE PayRails_System SHALL apply the rules to subsequent transactions
7. WHEN a Super_Admin views reconciliation alerts, THE PayRails_System SHALL display all unresolved discrepancies

### Requirement 10: Integrator Revenue Tracking

**User Story:** As an Integrator, I want to track my earnings from merchant transactions, so that I can monitor my revenue and optimize my pricing strategy.

#### Acceptance Criteria

1. WHEN an Integrator views their revenue dashboard, THE PayRails_System SHALL display total Revenue_Share earned
2. WHEN an Integrator views transaction details, THE PayRails_System SHALL show the markup amount for each transaction
3. WHEN an Integrator filters by merchant, THE PayRails_System SHALL display revenue breakdown by client
4. WHEN an Integrator views trends, THE PayRails_System SHALL display revenue over time with charts
5. WHEN an Integrator exports revenue data, THE PayRails_System SHALL generate a detailed report with all transactions
6. WHEN a transaction is refunded, THE PayRails_System SHALL adjust the Integrator's revenue accordingly
7. WHEN an Integrator views payout history, THE PayRails_System SHALL display all completed payouts with dates and amounts

### Requirement 11: API Key Management

**User Story:** As an Integrator, I want to generate and manage API keys for my merchants, so that they can integrate with PayRails securely.

#### Acceptance Criteria

1. WHEN an Integrator generates API keys, THE PayRails_System SHALL create a Client ID and Client Secret pair
2. WHEN API keys are generated, THE PayRails_System SHALL assign appropriate OAuth2 scopes based on merchant permissions
3. WHEN an Integrator rotates API keys, THE PayRails_System SHALL invalidate old keys and generate new ones
4. WHEN an Integrator views API keys, THE PayRails_System SHALL display the Client ID but mask the Client Secret
5. WHEN API keys are used, THE PayRails_System SHALL log all API requests with timestamps and endpoints
6. WHEN an Integrator revokes API keys, THE PayRails_System SHALL immediately invalidate the credentials
7. WHERE sandbox mode is enabled, THE PayRails_System SHALL route API requests to test gateways

### Requirement 12: Webhook Management and Replay

**User Story:** As an Integrator, I want to view webhook logs and replay failed webhooks, so that I can debug integration issues and ensure no data is lost.

#### Acceptance Criteria

1. WHEN a webhook is sent to a merchant, THE PayRails_System SHALL log the request payload and response
2. WHEN an Integrator views webhook logs, THE PayRails_System SHALL display all webhooks with status codes and timestamps
3. WHEN a webhook fails, THE PayRails_System SHALL retry with exponential backoff up to 5 attempts
4. WHEN an Integrator replays a webhook, THE PayRails_System SHALL resend the original payload to the configured endpoint
5. WHEN webhook delivery fails after all retries, THE PayRails_System SHALL move it to the DLQ
6. WHEN an Integrator views webhook details, THE PayRails_System SHALL display full request and response bodies
7. WHEN an Integrator filters webhooks, THE PayRails_System SHALL filter by merchant, status, or date range

### Requirement 13: Compliance and KYB Workflow

**User Story:** As a Super Admin, I want to review and approve merchant KYB submissions before activating live API access, so that the platform maintains regulatory compliance.

#### Acceptance Criteria

1. WHEN a Merchant submits KYB documents, THE PayRails_System SHALL store the documents securely
2. WHEN KYB is submitted, THE PayRails_System SHALL create a review task for Super_Admin
3. WHEN a Super_Admin reviews KYB, THE PayRails_System SHALL display all submitted documents and business information
4. WHEN a Super_Admin approves KYB, THE PayRails_System SHALL activate live API access for the merchant
5. WHEN a Super_Admin rejects KYB, THE PayRails_System SHALL notify the merchant with rejection reasons
6. WHEN KYB status changes, THE PayRails_System SHALL log the change with timestamp and admin user
7. IF a Merchant attempts to use live API without KYB approval, THEN THE PayRails_System SHALL reject the request

### Requirement 14: Merchant Freeze and Kill Switch

**User Story:** As a Super Admin, I want to instantly freeze a merchant's account if fraud is detected, so that I can prevent further unauthorized transactions.

#### Acceptance Criteria

1. WHEN a Super_Admin freezes a merchant, THE PayRails_System SHALL immediately block all API requests from that merchant
2. WHEN a merchant is frozen, THE PayRails_System SHALL reject all pending payment requests
3. WHEN a frozen merchant attempts a transaction, THE PayRails_System SHALL return an error indicating account suspension
4. WHEN a Super_Admin unfreezes a merchant, THE PayRails_System SHALL restore full API access
5. WHEN a merchant is frozen, THE PayRails_System SHALL notify the Integrator and Merchant via email
6. WHEN a freeze action occurs, THE PayRails_System SHALL log the action with reason, timestamp, and admin user
7. WHEN a Super_Admin views frozen accounts, THE PayRails_System SHALL display all suspended merchants with freeze reasons

### Requirement 15: Gateway Credential Management

**User Story:** As an Integrator, I want to securely configure payment gateway credentials for my merchants, so that they can accept payments without handling sensitive keys directly.

#### Acceptance Criteria

1. WHEN an Integrator enters gateway credentials, THE PayRails_System SHALL store them in OpenBao
2. WHEN credentials are stored, THE PayRails_System SHALL encrypt them before storage
3. WHEN a Gateway_Adapter needs credentials, THE PayRails_System SHALL retrieve them from OpenBao
4. WHEN an Integrator updates credentials, THE PayRails_System SHALL validate them against the Payment_Provider API
5. WHEN credentials are invalid, THE PayRails_System SHALL notify the Integrator and prevent activation
6. WHEN an Integrator views credentials, THE PayRails_System SHALL display masked values only
7. THE PayRails_System SHALL never store gateway credentials in the PostgreSQL database

### Requirement 16: Payment Link Generation

**User Story:** As a Merchant, I want to generate payment links that I can share with customers, so that I can collect payments without building a custom checkout.

#### Acceptance Criteria

1. WHEN a Merchant creates a payment link, THE PayRails_System SHALL generate a unique URL
2. WHEN a payment link is created, THE PayRails_System SHALL allow configuration of amount, description, and expiry
3. WHEN a customer visits a payment link, THE PayRails_System SHALL display a payment page with configured details
4. WHEN a customer completes payment, THE PayRails_System SHALL process it through the configured gateway
5. WHEN a payment link expires, THE PayRails_System SHALL reject payment attempts with an expiry message
6. WHEN a Merchant views payment links, THE PayRails_System SHALL display all links with status and usage statistics
7. WHEN a payment link is used, THE PayRails_System SHALL notify the Merchant via webhook

### Requirement 17: Batch Payment Processing

**User Story:** As a Merchant, I want to upload a CSV file with multiple payment requests and process them in bulk, so that I can efficiently handle payroll or supplier payments.

#### Acceptance Criteria

1. WHEN a Merchant uploads a batch file, THE PayRails_System SHALL validate the CSV format and data
2. WHEN batch validation succeeds, THE PayRails_System SHALL create individual payment requests for each row
3. WHEN batch processing starts, THE PayRails_System SHALL process payments asynchronously using Redis queue
4. WHEN each payment completes, THE PayRails_System SHALL update the batch status
5. WHEN batch processing completes, THE PayRails_System SHALL notify the Merchant with success and failure counts
6. WHEN a batch payment fails, THE PayRails_System SHALL log the error and continue processing remaining payments
7. WHEN a Merchant views batch history, THE PayRails_System SHALL display all batches with completion status

### Requirement 18: ERP Integration Connectors

**User Story:** As a Merchant, I want to sync my PayRails transactions with my ERP system (ERPNext or QuickBooks), so that I maintain a single source of truth for financial records.

#### Acceptance Criteria

1. WHEN a Merchant connects an ERP system, THE PayRails_System SHALL authenticate using OAuth2
2. WHEN a transaction is completed, THE PayRails_System SHALL sync the transaction to the connected ERP
3. WHEN ERP sync fails, THE PayRails_System SHALL queue the sync for retry
4. WHEN a Merchant views ERP sync status, THE PayRails_System SHALL display last sync time and any errors
5. WHEN a Merchant disconnects an ERP, THE PayRails_System SHALL revoke OAuth tokens
6. WHEN syncing to ERPNext, THE PayRails_System SHALL create payment entries with proper account mapping
7. WHEN syncing to QuickBooks, THE PayRails_System SHALL create sales receipts with appropriate tax codes

### Requirement 19: Smart Routing and Failover

**User Story:** As a Super Admin, I want to configure intelligent routing rules that automatically switch to backup gateways when the primary fails, so that payment acceptance remains uninterrupted.

#### Acceptance Criteria

1. WHEN a Super_Admin configures routing rules, THE PayRails_System SHALL store priority order for gateways
2. WHEN a payment is initiated, THE PayRails_System SHALL select the gateway based on routing rules
3. WHEN a gateway fails, THE Failover_Logic SHALL automatically route to the next available gateway
4. WHEN failover occurs, THE PayRails_System SHALL log the failover event with reason
5. WHEN a gateway recovers, THE PayRails_System SHALL restore it to the routing pool
6. WHEN routing rules include conditions, THE PayRails_System SHALL evaluate amount thresholds or time-based rules
7. WHEN a Super_Admin views routing analytics, THE PayRails_System SHALL display gateway usage distribution

### Requirement 20: Spend Rules Engine

**User Story:** As a Merchant, I want to configure spending rules for wallets (daily limits, approval thresholds, allowed categories), so that I can enforce financial controls automatically.

#### Acceptance Criteria

1. WHEN a Merchant creates a spend rule, THE Spend_Rules_Engine SHALL validate the rule configuration
2. WHEN a wallet transaction is initiated, THE Spend_Rules_Engine SHALL evaluate all applicable rules
3. WHEN a transaction violates a rule, THE PayRails_System SHALL reject the transaction with a clear reason
4. WHEN a rule requires approval, THE PayRails_System SHALL create a Maker_Checker workflow
5. WHEN daily limits are configured, THE Spend_Rules_Engine SHALL track cumulative spending per day
6. WHEN a Merchant updates rules, THE PayRails_System SHALL apply changes to subsequent transactions immediately
7. WHEN a Merchant views rule violations, THE PayRails_System SHALL display all rejected transactions with rule details

### Requirement 21: Receipt Upload and Attachment

**User Story:** As a Merchant user, I want to upload receipts and attach them to wallet transactions, so that I can maintain proper documentation for expense tracking and audits.

#### Acceptance Criteria

1. WHEN a user uploads a receipt, THE PayRails_System SHALL validate the file type and size
2. WHEN a receipt is uploaded, THE PayRails_System SHALL store it securely with encryption
3. WHEN a user attaches a receipt to a transaction, THE PayRails_System SHALL link the file to the transaction record
4. WHEN a user views a transaction, THE PayRails_System SHALL display all attached receipts
5. WHEN a user downloads a receipt, THE PayRails_System SHALL serve the original file
6. WHEN a Merchant exports transactions, THE PayRails_System SHALL include receipt download links
7. WHEN a receipt is deleted, THE PayRails_System SHALL remove the file and unlink it from transactions

### Requirement 22: Real-Time Balance Tracking

**User Story:** As a Merchant, I want to see my current wallet balance update in real-time as transactions occur, so that I always know my available funds.

#### Acceptance Criteria

1. WHEN a transaction completes, THE PayRails_System SHALL update the wallet balance immediately
2. WHEN a Merchant views their dashboard, THE PayRails_System SHALL display current balance from SasaPay
3. WHEN multiple transactions occur simultaneously, THE Shadow_Ledger SHALL maintain consistency using database transactions
4. WHEN a balance query is made, THE PayRails_System SHALL return the balance within 200ms
5. WHEN a discrepancy is detected, THE Liquidity_Monitor SHALL flag it for reconciliation
6. WHEN a Merchant has multiple wallets, THE PayRails_System SHALL display individual and total balances
7. WHEN balance updates occur, THE PayRails_System SHALL broadcast updates via WebSocket for real-time UI refresh

### Requirement 23: Audit Trail and Logging

**User Story:** As a Super Admin, I want comprehensive audit logs of all financial transactions and system actions, so that I can investigate issues and maintain compliance.

#### Acceptance Criteria

1. WHEN any financial transaction occurs, THE PayRails_System SHALL create an immutable audit log entry
2. WHEN a user performs an administrative action, THE PayRails_System SHALL log the action with user ID and timestamp
3. WHEN a Super_Admin views audit logs, THE PayRails_System SHALL display filterable logs with full details
4. WHEN sensitive data is logged, THE PayRails_System SHALL mask PII and credentials
5. WHEN audit logs are queried, THE PayRails_System SHALL support filtering by user, action type, date range, and entity
6. THE PayRails_System SHALL retain audit logs for a minimum of 7 years
7. WHEN an audit log is created, THE PayRails_System SHALL ensure it cannot be modified or deleted

### Requirement 24: Rate Limiting and Throttling

**User Story:** As a Super Admin, I want API rate limiting to prevent abuse and ensure fair usage, so that the platform remains stable and performant for all users.

#### Acceptance Criteria

1. WHEN an API request is received, THE PayRails_System SHALL check the rate limit for the client
2. WHEN rate limits are exceeded, THE PayRails_System SHALL reject requests with HTTP 429 status
3. WHEN rate limits are configured, THE PayRails_System SHALL store limits in Redis for fast access
4. WHEN a Super_Admin sets custom rate limits, THE PayRails_System SHALL apply them to the specified client
5. WHEN rate limit headers are requested, THE PayRails_System SHALL include X-RateLimit-Limit and X-RateLimit-Remaining
6. WHEN a client is rate limited, THE PayRails_System SHALL log the event for monitoring
7. WHERE different API endpoints exist, THE PayRails_System SHALL apply endpoint-specific rate limits

### Requirement 25: Notification System

**User Story:** As a user of any level, I want to receive notifications about important events (payments received, approvals needed, system alerts), so that I can respond promptly to critical actions.

#### Acceptance Criteria

1. WHEN a payment is received, THE PayRails_System SHALL send a notification to the Merchant
2. WHEN a payment requires approval, THE PayRails_System SHALL notify users with payment:approve scope
3. WHEN a system alert occurs, THE PayRails_System SHALL notify Super_Admin users
4. WHEN a user configures notification preferences, THE PayRails_System SHALL respect channel preferences (email, SMS, in-app)
5. WHEN a notification is sent, THE PayRails_System SHALL log the delivery status
6. WHEN a notification fails, THE PayRails_System SHALL retry up to 3 times
7. WHEN a user views notifications, THE PayRails_System SHALL display unread count and notification history

### Requirement 26: Sandbox and Testing Environment

**User Story:** As an Integrator, I want to test my integration in a sandbox environment before going live, so that I can validate my implementation without affecting real transactions.

#### Acceptance Criteria

1. WHEN an Integrator toggles to sandbox mode, THE PayRails_System SHALL route all requests to test gateways
2. WHEN sandbox mode is active, THE PayRails_System SHALL clearly indicate the environment in all responses
3. WHEN test transactions are created, THE PayRails_System SHALL not affect live balances or ledgers
4. WHEN an Integrator generates sandbox API keys, THE PayRails_System SHALL create separate credentials from live keys
5. WHEN sandbox webhooks are sent, THE PayRails_System SHALL use test webhook URLs
6. WHEN an Integrator views sandbox data, THE PayRails_System SHALL isolate it from production data
7. WHEN KYB is not approved, THE PayRails_System SHALL restrict the merchant to sandbox mode only

### Requirement 27: Transaction Search and Filtering

**User Story:** As a Merchant, I want to search and filter my transaction history by multiple criteria, so that I can quickly find specific transactions for reconciliation or reporting.

#### Acceptance Criteria

1. WHEN a Merchant searches transactions, THE PayRails_System SHALL support search by transaction ID, amount, or customer reference
2. WHEN a Merchant applies filters, THE PayRails_System SHALL filter by date range, gateway, status, or tags
3. WHEN search results are displayed, THE PayRails_System SHALL paginate results with configurable page size
4. WHEN a Merchant exports filtered results, THE PayRails_System SHALL export only the filtered transactions
5. WHEN a Merchant saves a filter, THE PayRails_System SHALL store the filter configuration for reuse
6. WHEN search queries are complex, THE PayRails_System SHALL return results within 2 seconds
7. WHEN a Merchant sorts results, THE PayRails_System SHALL support sorting by date, amount, or status

### Requirement 28: Currency Support and Conversion

**User Story:** As a Merchant operating across multiple African countries, I want to accept payments in different currencies and view consolidated reports, so that I can manage multi-currency operations.

#### Acceptance Criteria

1. WHEN a payment is received in a foreign currency, THE PayRails_System SHALL record the original currency and amount
2. WHEN displaying balances, THE PayRails_System SHALL support viewing in merchant's base currency
3. WHEN currency conversion is needed, THE PayRails_System SHALL use current exchange rates from a reliable source
4. WHEN a Merchant views reports, THE PayRails_System SHALL display amounts in both original and base currency
5. WHEN exchange rates are updated, THE PayRails_System SHALL refresh rates at least daily
6. WHEN a transaction involves conversion, THE Shadow_Ledger SHALL record both currency amounts
7. WHEN a Merchant configures base currency, THE PayRails_System SHALL apply it to all dashboard displays

### Requirement 29: Performance Monitoring and Alerting

**User Story:** As a Super Admin, I want automated monitoring of system performance with alerts for anomalies, so that I can proactively address issues before they impact users.

#### Acceptance Criteria

1. WHEN system latency exceeds thresholds, THE PayRails_System SHALL send alerts to Super_Admin
2. WHEN gateway success rates drop below 95%, THE PayRails_System SHALL trigger an alert
3. WHEN database query times exceed 1 second, THE PayRails_System SHALL log slow query warnings
4. WHEN API error rates spike, THE PayRails_System SHALL notify the operations team
5. WHEN Redis queue depth exceeds thresholds, THE PayRails_System SHALL alert about potential backlog
6. WHEN a Super_Admin views performance metrics, THE PayRails_System SHALL display real-time dashboards
7. WHEN alerts are configured, THE PayRails_System SHALL support multiple notification channels (email, Slack, SMS)

### Requirement 30: Data Export and Reporting

**User Story:** As a Merchant, I want to export my transaction data in various formats for accounting and analysis, so that I can integrate with external tools and meet reporting requirements.

#### Acceptance Criteria

1. WHEN a Merchant requests an export, THE PayRails_System SHALL support CSV, Excel, and JSON formats
2. WHEN exporting transactions, THE PayRails_System SHALL include all relevant fields (date, amount, gateway, fees, status)
3. WHEN exports are large, THE PayRails_System SHALL process them asynchronously and notify when ready
4. WHEN a Merchant downloads an export, THE PayRails_System SHALL serve the file with appropriate headers
5. WHEN exporting for accounting, THE PayRails_System SHALL include Fiscal_Receipt numbers and tax information
6. WHEN a Merchant schedules recurring exports, THE PayRails_System SHALL generate and email reports automatically
7. WHEN export requests are made, THE PayRails_System SHALL limit file size to prevent system overload

### Requirement 31: Multi-Market Tax System Integration

**User Story:** As an Integrator, I want to enable tax system integrations for different African markets (Kenya, Rwanda, Tanzania, Burundi, Nigeria), so that my clients can remain compliant regardless of their operating country.

#### Acceptance Criteria

1. WHEN an Integrator views available tax systems, THE PayRails_System SHALL display all supported Tax_System options by country
2. WHEN an Integrator enables a Tax_System for a merchant, THE PayRails_System SHALL configure the appropriate API adapter
3. WHEN a merchant transaction occurs, THE PayRails_System SHALL send fiscal data to the enabled Tax_System
4. WHEN a Tax_System responds with a receipt, THE PayRails_System SHALL store it with the transaction
5. WHEN an Integrator connects an invoicing tool without tax integration, THE PayRails_System SHALL bridge the tool to the Tax_System
6. WHEN multiple Tax_Systems are configured, THE PayRails_System SHALL route fiscal data based on merchant country
7. WHEN a Tax_System is unavailable, THE PayRails_System SHALL queue fiscal submissions for retry

### Requirement 32: Multiple Gateway App Configurations

**User Story:** As a Merchant, I want to configure multiple M-Pesa paybills or tills as separate apps, so that I can manage different business units or locations with distinct payment endpoints.

#### Acceptance Criteria

1. WHEN a Merchant creates a new app configuration, THE PayRails_System SHALL generate a unique app_id
2. WHEN an app is configured, THE PayRails_System SHALL store gateway-specific parameters (paybill number, till number, shortcode)
3. WHEN a payment is received, THE PayRails_System SHALL identify the source app based on gateway credentials
4. WHEN a Merchant views apps, THE PayRails_System SHALL display all App_Configuration entries with their status
5. WHEN an Integrator enables STK_Push for an app, THE PayRails_System SHALL validate the credentials with the gateway
6. WHEN multiple apps exist, THE PayRails_System SHALL allow merchants to select the source app for transactions
7. WHEN an app is disabled, THE PayRails_System SHALL reject incoming payments to that configuration

### Requirement 33: Integrator Revenue Share from Platform Fees

**User Story:** As an Integrator, I want to earn recurring revenue share from monthly platform fees charged to my merchants, so that I have predictable income from my client base.

#### Acceptance Criteria

1. WHEN a Merchant is billed a Monthly_Platform_Fee, THE PayRails_System SHALL calculate the Integrator's revenue share percentage
2. WHEN platform fees are collected, THE PayRails_System SHALL credit the Integrator's revenue account
3. WHEN an Integrator views revenue, THE PayRails_System SHALL separate transaction-based and subscription-based earnings
4. WHEN a Merchant cancels their subscription, THE PayRails_System SHALL stop revenue share payments to the Integrator
5. WHEN an Integrator sets custom pricing, THE PayRails_System SHALL allow markup configuration on platform fees
6. WHEN monthly billing occurs, THE PayRails_System SHALL generate invoices for both Merchant and Integrator
7. WHEN revenue share is calculated, THE Shadow_Ledger SHALL record the split between platform and Integrator

### Requirement 34: Direct Payment Management Without External Systems

**User Story:** As a Merchant, I want to initiate STK Push payments and reconcile transactions directly from the platform, so that I can operate without additional accounting systems.

#### Acceptance Criteria

1. WHEN a Merchant initiates an STK_Push, THE PayRails_System SHALL send the request to the configured gateway
2. WHEN an STK_Push is sent, THE PayRails_System SHALL display the request status in real-time
3. WHEN a Merchant views payment reconciliation, THE PayRails_System SHALL match incoming payments to expected transactions
4. WHEN unmatched payments exist, THE PayRails_System SHALL display them in the unidentified payments bin
5. WHEN a Merchant manually reconciles a payment, THE PayRails_System SHALL update the transaction status
6. WHEN reconciliation is automated, THE PayRails_System SHALL match payments based on reference numbers or amounts
7. WHEN a Merchant views transaction history, THE PayRails_System SHALL provide complete visibility without external tools

### Requirement 35: Withdrawal Request Management

**User Story:** As a Merchant, I want to request withdrawals from my wallet to external gateways through the platform, so that I can access my funds when needed.

#### Acceptance Criteria

1. WHEN a Merchant creates a Withdrawal_Request, THE PayRails_System SHALL validate sufficient wallet balance
2. WHEN a withdrawal is requested, THE PayRails_System SHALL apply Spend_Rules_Engine validation
3. WHEN a withdrawal requires approval, THE PayRails_System SHALL create a Maker_Checker workflow
4. WHEN a withdrawal is approved, THE PayRails_System SHALL initiate the transfer via the selected gateway API
5. WHEN a withdrawal completes, THE PayRails_System SHALL update both Shadow_Ledger and wallet balance
6. WHEN a withdrawal fails, THE PayRails_System SHALL reverse the ledger entries and notify the Merchant
7. WHEN a Merchant views withdrawal history, THE PayRails_System SHALL display all requests with status and timestamps

### Requirement 36: Evolving Secrets Management Strategy

**User Story:** As a system architect, I want the platform to support both PostgreSQL-based and OpenBao-based secrets storage, so that we can migrate to more secure infrastructure without breaking existing functionality.

#### Acceptance Criteria

1. WHEN gateway credentials are stored, THE PayRails_System SHALL use a configurable secrets backend (PostgreSQL or OpenBao)
2. WHEN the secrets backend is PostgreSQL, THE PayRails_System SHALL encrypt credentials using AES-256
3. WHEN the secrets backend is OpenBao, THE PayRails_System SHALL store credentials in Vault with appropriate policies
4. WHEN credentials are retrieved, THE PayRails_System SHALL abstract the storage mechanism behind a common interface
5. WHEN migrating from PostgreSQL to OpenBao, THE PayRails_System SHALL support gradual migration without downtime
6. WHEN a Gateway_Adapter requests credentials, THE PayRails_System SHALL retrieve them from the configured backend
7. WHEN secrets backend configuration changes, THE PayRails_System SHALL apply changes without requiring code deployment

### Requirement 37: Integrated KYC Verification Services

**User Story:** As a Merchant, I want to verify customer identity through the platform dashboard and API, so that I can comply with KYC requirements without separate integrations.

#### Acceptance Criteria

1. WHEN a Merchant initiates KYC verification, THE PayRails_System SHALL send the request to configured verification partners
2. WHEN KYC verification completes, THE PayRails_System SHALL store the verification status and results
3. WHEN a Merchant views customer records, THE PayRails_System SHALL display KYC verification status
4. WHEN KYC is required for a transaction, THE PayRails_System SHALL enforce verification before processing
5. WHEN verification fails, THE PayRails_System SHALL provide clear reasons and allow retry
6. WHEN a Merchant uses the API, THE PayRails_System SHALL expose KYC verification endpoints
7. WHEN verification data is stored, THE PayRails_System SHALL encrypt sensitive identity information

### Requirement 38: Credit Reference Bureau Integration

**User Story:** As a Merchant, I want to check customer credit history through integrated CRB services (Metropol, Equity Jenga API), so that I can make informed lending or credit decisions.

#### Acceptance Criteria

1. WHEN a Merchant requests a CRB check, THE PayRails_System SHALL send the query to the configured CRB provider
2. WHEN a CRB report is received, THE PayRails_System SHALL display the credit score and history
3. WHEN CRB checks are performed, THE PayRails_System SHALL log all requests for audit purposes
4. WHEN a Merchant configures CRB preferences, THE PayRails_System SHALL allow selection of preferred providers
5. WHEN CRB data is accessed, THE PayRails_System SHALL enforce proper authorization and consent
6. WHEN a Merchant uses the API, THE PayRails_System SHALL expose CRB checking endpoints
7. WHEN CRB checks fail, THE PayRails_System SHALL provide error details and retry options

### Requirement 39: Third-Party Integration Partner Management

**User Story:** As a Super Admin, I want to manage third-party integration partners (Metropol, Equity Jenga, SmileID) from a central configuration, so that I can easily add or update service providers.

#### Acceptance Criteria

1. WHEN a Super_Admin adds an integration partner, THE PayRails_System SHALL store the partner configuration
2. WHEN partner credentials are configured, THE PayRails_System SHALL store them in the secrets backend
3. WHEN a partner API is called, THE PayRails_System SHALL use the configured credentials and endpoints
4. WHEN a partner service is unavailable, THE PayRails_System SHALL log the outage and notify administrators
5. WHEN partner configurations change, THE PayRails_System SHALL apply updates without requiring system restart
6. WHEN a Super_Admin views partner status, THE PayRails_System SHALL display health metrics and usage statistics
7. WHEN a partner is disabled, THE PayRails_System SHALL prevent new requests while allowing pending requests to complete
