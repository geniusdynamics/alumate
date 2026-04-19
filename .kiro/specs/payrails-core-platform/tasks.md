# Implementation Plan: PayRails.Core Platform

## Overview

This implementation plan breaks down the PayRails.Core platform into discrete, incremental coding tasks. The platform is a unified African financial middleware aggregation system built with Go, PostgreSQL, Redis, and OpenBao. The implementation follows a phased approach: Foundation (core infrastructure and basic payment collection), Compliance & Integration (tax systems and ERP connectors), and WaaS & Advanced Features (wallet services and spend management).

Each task builds on previous work, with regular checkpoints to ensure quality. The plan emphasizes property-based testing alongside unit tests to validate correctness properties defined in the design document.

## Tasks

- [ ] 1. Set up project structure and core infrastructure
  - Initialize Go module with hexagonal architecture layout
  - Set up PostgreSQL database with initial schema
  - Configure Redis for queues and caching
  - Create Docker Compose for local development
  - Set up testing framework (standard library + gopter for property tests)
  - _Requirements: All (foundational)_

- [ ] 2. Implement authentication and authorization system
  - [ ] 2.1 Create OAuth2 authentication service
    - Implement token generation with JWT
    - Create user authentication endpoints
    - Implement token validation middleware
    - _Requirements: 1.1, 1.2_
  
  - [ ]* 2.2 Write property test for authentication
    - **Property 1: Authentication Credentials Validation**
    - **Validates: Requirements 1.1**
  
  - [ ]* 2.3 Write property test for token scope assignment
    - **Property 2: Token Scope Assignment**
    - **Validates: Requirements 1.2**
  
  - [ ] 2.4 Implement 2FA verification system
    - Create 2FA code generation and validation
    - Store 2FA secrets securely
    - Implement 2FA enforcement for sensitive scopes
    - _Requirements: 1.3_
  
  - [ ]* 2.5 Write property test for 2FA enforcement
    - **Property 3: 2FA Enforcement for Sensitive Scopes**
    - **Validates: Requirements 1.3**
  
  - [ ] 2.6 Implement scope-based authorization middleware
    - Create scope validation logic
    - Implement authorization checks for all endpoints
    - _Requirements: 1.4_
  
  - [ ]* 2.7 Write property test for scope-based authorization
    - **Property 4: Scope-Based Authorization**
    - **Validates: Requirements 1.4**
  
  - [ ] 2.8 Implement account lockout mechanism
    - Track failed login attempts in Redis
    - Implement automatic account locking
    - Create admin notification system
    - _Requirements: 1.7_
  
  - [ ]* 2.9 Write property test for account lockout
    - **Property 5: Account Lockout After Failed Attempts**
    - **Validates: Requirements 1.7**


- [ ] 3. Implement secrets management abstraction
  - [ ] 3.1 Create SecretsBackend interface
    - Define interface for storing and retrieving secrets
    - Create common error types
    - _Requirements: 36.1, 36.4_
  
  - [ ] 3.2 Implement PostgreSQL secrets backend
    - Create encrypted storage using AES-256
    - Implement all interface methods
    - _Requirements: 36.1_
  
  - [ ] 3.3 Implement OpenBao secrets backend
    - Integrate with OpenBao Vault client
    - Implement all interface methods
    - _Requirements: 36.1_
  
  - [ ]* 3.4 Write property test for secrets backend abstraction
    - **Property 57: Secrets Backend Abstraction**
    - **Validates: Requirements 36.4**
  
  - [ ]* 3.5 Write property test for secrets storage location
    - **Property 8: Secrets Backend Storage**
    - **Validates: Requirements 2.4, 15.1, 15.7, 36.1**

- [ ] 4. Implement core domain models and database layer
  - [ ] 4.1 Create user, integrator, and merchant models
    - Implement Eloquent-style models with relationships
    - Create database migrations
    - Implement model factories for testing
    - _Requirements: 1.1, 2.1, 2.2_
  
  - [ ]* 4.2 Write property test for unique identifier generation
    - **Property 6: Unique Identifier Generation**
    - **Validates: Requirements 2.1, 32.1**
  
  - [ ] 4.3 Create wallet and account models
    - Implement wallet hierarchy support
    - Create accounts for ledger system
    - _Requirements: 6.1, 6.2_
  
  - [ ] 4.4 Create transaction and ledger entry models
    - Implement transaction model with metadata support
    - Create ledger entries for double-entry bookkeeping
    - _Requirements: 3.3, 4.1_
  
  - [ ] 4.5 Create app configuration model
    - Support multiple gateway configurations per merchant
    - Store gateway-specific parameters
    - _Requirements: 32.1, 32.2_

- [ ] 5. Implement Shadow Ledger service
  - [ ] 5.1 Create LedgerService with double-entry logic
    - Implement RecordTransaction method
    - Ensure debits equal credits
    - Use database transactions for atomicity
    - _Requirements: 4.1, 4.6_
  
  - [ ]* 5.2 Write property test for double-entry integrity
    - **Property 12: Double-Entry Ledger Integrity**
    - **Validates: Requirements 4.1, 4.6**
  
  - [ ]* 5.3 Write property test for ledger recording completeness
    - **Property 10: Ledger Recording Completeness**
    - **Validates: Requirements 3.3, 6.5, 35.5**
  
  - [ ] 5.4 Implement balance calculation methods
    - GetBalance for accounts and wallets
    - Optimize with caching in Redis
    - _Requirements: 6.4, 22.1_
  
  - [ ]* 5.5 Write property test for immediate balance updates
    - **Property 40: Immediate Balance Updates**
    - **Validates: Requirements 22.1**
  
  - [ ] 5.6 Implement concurrent transaction safety
    - Use database transaction isolation
    - Implement optimistic locking where needed
    - _Requirements: 22.3_
  
  - [ ]* 5.7 Write property test for concurrent transaction safety
    - **Property 41: Concurrent Transaction Safety**
    - **Validates: Requirements 22.3**

- [ ] 6. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 7. Implement PaymentProvider interface and M-Pesa adapter
  - [ ] 7.1 Define PaymentProvider interface
    - Create interface with all required methods
    - Define request/response types
    - _Requirements: 3.1_
  
  - [ ] 7.2 Implement M-Pesa Daraja API adapter
    - Implement C2B (Customer to Business) collection
    - Implement STK Push for payment initiation
    - Handle webhook/IPN processing
    - _Requirements: 3.1, 3.2, 34.1_
  
  - [ ]* 7.3 Write property test for gateway routing
    - **Property 9: Gateway Routing Correctness**
    - **Validates: Requirements 3.1**
  
  - [ ]* 7.4 Write property test for STK Push routing
    - **Property 54: STK Push Gateway Routing**
    - **Validates: Requirements 34.1**
  
  - [ ] 7.3 Implement webhook normalization
    - Parse M-Pesa IPN format
    - Normalize to internal format
    - _Requirements: 3.2_

- [ ] 8. Implement payment processing service
  - [ ] 8.1 Create PaymentService
    - Implement InitiatePayment method
    - Route to appropriate gateway adapter
    - Record transaction in Shadow Ledger
    - _Requirements: 3.1, 3.3, 3.7_
  
  - [ ]* 8.2 Write property test for fee calculation
    - **Property 11: Fee Calculation Accuracy**
    - **Validates: Requirements 3.7**
  
  - [ ] 8.3 Implement webhook handling endpoint
    - Receive and validate webhooks
    - Update transaction status
    - Trigger merchant notifications
    - _Requirements: 3.2, 3.4_
  
  - [ ]* 8.4 Write property test for webhook logging
    - **Property 28: Webhook Logging Completeness**
    - **Validates: Requirements 12.1**
  
  - [ ] 8.5 Implement webhook retry logic
    - Exponential backoff for failed deliveries
    - Move to DLQ after 5 attempts
    - _Requirements: 12.3_
  
  - [ ]* 8.6 Write property test for webhook retry
    - **Property 29: Webhook Retry Logic**
    - **Validates: Requirements 12.3**

- [ ] 9. Implement integrator management service
  - [ ] 9.1 Create IntegratorService
    - Implement merchant creation
    - Auto-generate merchant_id
    - Auto-create SasaPay wallet
    - _Requirements: 2.1, 2.2_
  
  - [ ]* 9.2 Write property test for automatic resource provisioning
    - **Property 7: Automatic Resource Provisioning**
    - **Validates: Requirements 2.2, 6.1**
  
  - [ ] 9.3 Implement app configuration management
    - Create, update, delete app configurations
    - Store credentials in secrets backend
    - _Requirements: 2.4, 32.1, 32.2_
  
  - [ ]* 9.4 Write property test for app payment attribution
    - **Property 52: App Configuration Payment Attribution**
    - **Validates: Requirements 32.3**
  
  - [ ] 9.5 Implement API key generation
    - Generate Client ID and Client Secret
    - Assign OAuth2 scopes
    - Store hashed secrets
    - _Requirements: 11.1, 11.2_
  
  - [ ]* 9.6 Write property test for API key uniqueness
    - **Property 26: API Key Uniqueness**
    - **Validates: Requirements 11.1**

- [ ] 10. Implement merchant dashboard API endpoints
  - [ ] 10.1 Create income consolidation endpoint
    - Fetch transactions from all channels
    - Support filtering and pagination
    - _Requirements: 5.1, 5.2_
  
  - [ ]* 10.2 Write property test for multi-channel visibility
    - **Property 15: Multi-Channel Transaction Visibility**
    - **Validates: Requirements 5.1**
  
  - [ ] 10.3 Implement transaction tagging and allocation
    - Allow merchants to tag transactions
    - Allocate unidentified payments
    - _Requirements: 5.3, 5.5_
  
  - [ ]* 10.4 Write property test for unidentified payment categorization
    - **Property 16: Unidentified Payment Categorization**
    - **Validates: Requirements 5.4**
  
  - [ ] 10.5 Implement analytics endpoints
    - Income breakdown by channel
    - Gateway usage statistics
    - _Requirements: 5.6_

- [ ] 11. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 12. Implement routing engine
  - [ ] 12.1 Create RoutingEngine service
    - Implement rule evaluation logic
    - Support priority-based selection
    - _Requirements: 19.2_
  
  - [ ]* 12.2 Write property test for routing rule application
    - **Property 36: Routing Rule Application**
    - **Validates: Requirements 19.2**
  
  - [ ] 12.3 Implement failover logic
    - Detect gateway failures
    - Automatically route to backup
    - Log failover events
    - _Requirements: 19.3, 19.4_
  
  - [ ]* 12.4 Write property test for automatic failover
    - **Property 37: Automatic Failover Execution**
    - **Validates: Requirements 19.3**
  
  - [ ] 12.5 Implement gateway health monitoring
    - Track success rates and latency
    - Update routing based on health
    - _Requirements: 9.3, 19.5_

- [ ] 13. Implement additional gateway adapters
  - [ ] 13.1 Implement Airtel Money adapter
    - Follow PaymentProvider interface
    - Handle Airtel-specific webhook format
    - _Requirements: 3.1_
  
  - [ ] 13.2 Implement Equity Bank Jenga API adapter
    - Implement bank transfer functionality
    - Handle Jenga API authentication
    - _Requirements: 3.1_
  
  - [ ] 13.3 Implement SasaPay WaaS adapter
    - Wallet creation and management
    - Balance queries
    - Internal transfers
    - _Requirements: 6.1, 6.4, 6.5_

- [ ] 14. Implement wallet service (WaaS)
  - [ ] 14.1 Create WalletService
    - Implement CreateWallet method
    - Support wallet hierarchy
    - Integrate with SasaPay adapter
    - _Requirements: 6.1, 6.2_
  
  - [ ] 14.2 Implement wallet transfers
    - Transfer between wallets
    - Record in Shadow Ledger
    - _Requirements: 6.5, 6.6_
  
  - [ ] 14.3 Implement balance tracking
    - Real-time balance queries
    - Cache in Redis with TTL
    - _Requirements: 6.4, 22.1, 22.4_

- [ ] 15. Implement spend rules engine
  - [ ] 15.1 Create SpendRulesEngine service
    - Implement rule evaluation logic
    - Support multiple rule types
    - _Requirements: 20.1, 20.2_
  
  - [ ]* 15.2 Write property test for spend rules evaluation
    - **Property 38: Spend Rules Evaluation Completeness**
    - **Validates: Requirements 20.2**
  
  - [ ] 15.3 Implement spend limit enforcement
    - Check daily limits
    - Check transaction limits
    - Reject violations
    - _Requirements: 6.3, 20.3, 20.5_
  
  - [ ]* 15.4 Write property test for spend limit enforcement
    - **Property 17: Spend Limit Enforcement**
    - **Validates: Requirements 6.3, 20.3**

- [ ] 16. Implement maker-checker workflow
  - [ ] 16.1 Create payment request system
    - Create pending payment requests
    - Notify approvers
    - _Requirements: 7.1, 7.2_
  
  - [ ]* 16.2 Write property test for maker-checker initiation
    - **Property 18: Maker-Checker Workflow Initiation**
    - **Validates: Requirements 7.1**
  
  - [ ] 16.3 Implement approval logic
    - Validate approver permissions
    - Prevent self-approval
    - Execute approved payments
    - _Requirements: 7.3, 7.4, 7.6_
  
  - [ ]* 16.4 Write property test for self-approval prevention
    - **Property 19: Self-Approval Prevention**
    - **Validates: Requirements 7.6**
  
  - [ ] 16.5 Implement 2FA for approvals
    - Require 2FA verification
    - Validate 2FA codes
    - _Requirements: 7.7_

- [ ] 17. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 18. Implement withdrawal request management
  - [ ] 18.1 Create withdrawal request system
    - Validate wallet balance
    - Apply spend rules
    - Create maker-checker workflow if needed
    - _Requirements: 35.1, 35.2, 35.3_
  
  - [ ]* 18.2 Write property test for withdrawal balance validation
    - **Property 56: Withdrawal Balance Validation**
    - **Validates: Requirements 35.1**
  
  - [ ] 18.3 Implement withdrawal execution
    - Initiate gateway transfer
    - Update ledger and wallet balance
    - Handle failures with reversal
    - _Requirements: 35.4, 35.5, 35.6_

- [ ] 19. Implement tax system integration
  - [ ] 19.1 Define TaxSystem interface
    - Create interface for all tax systems
    - Define common request/response types
    - _Requirements: 31.1, 31.2_
  
  - [ ] 19.2 Implement eTIMS adapter (Kenya)
    - Submit transactions for fiscal receipts
    - Store receipt data
    - _Requirements: 8.1, 8.2_
  
  - [ ] 19.3 Implement additional tax system adapters
    - RRA (Rwanda), TRA (Tanzania), OBR (Burundi), FIRS (Nigeria)
    - Follow TaxSystem interface
    - _Requirements: 31.1, 31.2_
  
  - [ ]* 19.4 Write property test for tax submission completeness
    - **Property 20: Tax System Submission Completeness**
    - **Validates: Requirements 8.1, 31.3**
  
  - [ ]* 19.5 Write property test for fiscal receipt association
    - **Property 21: Fiscal Receipt Association**
    - **Validates: Requirements 8.2**
  
  - [ ] 19.6 Implement tax submission queue
    - Queue failed submissions for retry
    - Handle tax system unavailability
    - _Requirements: 8.4, 8.5, 31.7_

- [ ] 20. Implement KYC/CRB verification services
  - [ ] 20.1 Define VerificationProvider interface
    - Create interface for KYC and CRB services
    - Define request/response types
    - _Requirements: 37.1, 38.1_
  
  - [ ] 20.2 Implement verification adapters
    - SmileID for KYC
    - Metropol for CRB
    - Equity Jenga API for CRB
    - _Requirements: 37.1, 38.1_
  
  - [ ]* 20.3 Write property test for KYC verification routing
    - **Property 58: KYC Verification Routing**
    - **Validates: Requirements 37.1**
  
  - [ ]* 20.4 Write property test for CRB request routing
    - **Property 60: CRB Request Routing**
    - **Validates: Requirements 38.1**
  
  - [ ] 20.5 Implement KYC enforcement for transactions
    - Check verification status
    - Reject unverified transactions
    - _Requirements: 37.4_
  
  - [ ]* 20.6 Write property test for KYC transaction enforcement
    - **Property 59: KYC Transaction Enforcement**
    - **Validates: Requirements 37.4**

- [ ] 21. Implement reconciliation engine
  - [ ] 21.1 Create ReconciliationEngine service
    - Poll providers for transactions
    - Match against internal records
    - _Requirements: 4.2, 34.6_
  
  - [ ]* 21.2 Write property test for reconciliation balance comparison
    - **Property 13: Reconciliation Balance Comparison**
    - **Validates: Requirements 4.2**
  
  - [ ]* 21.3 Write property test for automated reconciliation matching
    - **Property 55: Automated Reconciliation Matching**
    - **Validates: Requirements 34.6**
  
  - [ ] 21.4 Implement discrepancy detection and alerting
    - Detect balance mismatches
    - Create alerts for Super Admin
    - _Requirements: 4.3, 4.7_
  
  - [ ]* 21.5 Write property test for discrepancy alert generation
    - **Property 14: Discrepancy Alert Generation**
    - **Validates: Requirements 4.3**
  
  - [ ] 21.6 Implement background reconciliation workers
    - Schedule periodic reconciliation
    - Process reconciliation queue
    - _Requirements: 4.2, 4.4_

- [ ] 22. Implement ERP integration connectors
  - [ ] 22.1 Create ERP connector interface
    - Define common interface for ERP systems
    - Support OAuth2 authentication
    - _Requirements: 18.1_
  
  - [ ] 22.2 Implement ERPNext connector
    - OAuth2 integration
    - Create payment entries
    - _Requirements: 18.1, 18.6_
  
  - [ ] 22.3 Implement QuickBooks connector
    - OAuth2 integration
    - Create sales receipts
    - _Requirements: 18.1, 18.7_
  
  - [ ]* 22.4 Write property test for ERP sync completeness
    - **Property 35: ERP Sync Completeness**
    - **Validates: Requirements 18.2**
  
  - [ ] 22.5 Implement ERP sync queue
    - Queue failed syncs for retry
    - Handle ERP unavailability
    - _Requirements: 18.3_

- [ ] 23. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 24. Implement Super Admin dashboard features
  - [ ] 24.1 Create revenue monitoring endpoints
    - Calculate gross volume, COGS, net revenue
    - Display integrator payouts
    - _Requirements: 9.1, 9.2_
  
  - [ ]* 24.2 Write property test for dashboard metrics accuracy
    - **Property 22: Dashboard Metrics Accuracy**
    - **Validates: Requirements 9.1**
  
  - [ ]* 24.3 Write property test for revenue component calculation
    - **Property 23: Revenue Component Calculation**
    - **Validates: Requirements 9.2**
  
  - [ ] 24.4 Implement liquidity monitoring
    - Track system balance vs API balance
    - Display discrepancies
    - _Requirements: 4.7, 9.5_
  
  - [ ] 24.5 Implement gateway orchestration UI
    - Configure routing rules
    - Manage gateway credentials
    - View gateway health
    - _Requirements: 9.2, 19.6_
  
  - [ ] 24.6 Implement compliance features
    - KYB approval queue
    - Merchant freeze/unfreeze
    - _Requirements: 13.1, 13.2, 13.3, 14.1_
  
  - [ ]* 24.7 Write property test for immediate credential revocation
    - **Property 27: Immediate Credential Revocation**
    - **Validates: Requirements 11.6, 14.1**
  
  - [ ]* 24.8 Write property test for KYB approval access control
    - **Property 30: KYB Approval Access Control**
    - **Validates: Requirements 13.7, 26.7**

- [ ] 25. Implement integrator revenue tracking
  - [ ] 25.1 Create revenue dashboard endpoints
    - Display total revenue share
    - Show transaction-level markup
    - Filter by merchant
    - _Requirements: 10.1, 10.2, 10.3_
  
  - [ ]* 25.2 Write property test for integrator revenue tracking
    - **Property 24: Integrator Revenue Tracking**
    - **Validates: Requirements 10.1**
  
  - [ ] 25.2 Implement platform fee billing
    - Calculate monthly platform fees
    - Calculate integrator revenue share
    - Record in Shadow Ledger
    - _Requirements: 33.1, 33.2, 33.7_
  
  - [ ]* 25.3 Write property test for platform fee revenue share
    - **Property 53: Platform Fee Revenue Share Calculation**
    - **Validates: Requirements 33.1, 33.7**
  
  - [ ] 25.4 Implement refund handling
    - Reverse fee allocations
    - Adjust integrator revenue
    - _Requirements: 10.6_
  
  - [ ]* 25.5 Write property test for refund revenue adjustment
    - **Property 25: Refund Revenue Adjustment**
    - **Validates: Requirements 10.6**

- [ ] 26. Implement payment links and batch processing
  - [ ] 26.1 Create payment link generation
    - Generate unique URLs
    - Configure amount and expiry
    - Create payment page
    - _Requirements: 16.1, 16.2, 16.3_
  
  - [ ]* 26.2 Write property test for payment link expiry
    - **Property 32: Payment Link Expiry Enforcement**
    - **Validates: Requirements 16.5**
  
  - [ ] 26.3 Implement batch payment processing
    - Parse and validate CSV files
    - Create individual payment requests
    - Process asynchronously
    - _Requirements: 17.1, 17.2, 17.3_
  
  - [ ]* 26.4 Write property test for batch processing completeness
    - **Property 33: Batch Processing Completeness**
    - **Validates: Requirements 17.2**
  
  - [ ]* 26.5 Write property test for batch error isolation
    - **Property 34: Batch Error Isolation**
    - **Validates: Requirements 17.6**

- [ ] 27. Implement notification system
  - [ ] 27.1 Create NotificationService
    - Support multiple channels (email, SMS, in-app)
    - Queue notifications in Redis
    - _Requirements: 25.1, 25.2, 25.3_
  
  - [ ]* 27.2 Write property test for payment notification delivery
    - **Property 45: Payment Notification Delivery**
    - **Validates: Requirements 25.1**
  
  - [ ] 27.3 Implement notification preferences
    - Allow users to configure channels
    - Respect preferences in delivery
    - _Requirements: 25.4_
  
  - [ ] 27.4 Implement notification retry logic
    - Retry failed deliveries
    - Log delivery status
    - _Requirements: 25.6_

- [ ] 28. Implement audit logging and compliance features
  - [ ] 28.1 Create audit logging system
    - Log all financial transactions
    - Log administrative actions
    - Ensure immutability
    - _Requirements: 23.1, 23.2, 23.7_
  
  - [ ]* 28.2 Write property test for audit log completeness
    - **Property 42: Audit Log Completeness**
    - **Validates: Requirements 23.1, 23.7, 38.3**
  
  - [ ]* 28.3 Write property test for audit log retention
    - **Property 43: Audit Log Retention**
    - **Validates: Requirements 23.6**
  
  - [ ] 28.4 Implement receipt upload and attachment
    - Upload and encrypt receipts
    - Attach to transactions
    - _Requirements: 21.1, 21.2, 21.3_
  
  - [ ]* 28.5 Write property test for receipt encryption
    - **Property 39: Receipt Encryption**
    - **Validates: Requirements 21.2**

- [ ] 29. Implement rate limiting and performance features
  - [ ] 29.1 Create rate limiting middleware
    - Use Redis for rate limit counters
    - Return HTTP 429 when exceeded
    - Include rate limit headers
    - _Requirements: 24.1, 24.2, 24.3_
  
  - [ ]* 29.2 Write property test for rate limit enforcement
    - **Property 44: Rate Limit Enforcement**
    - **Validates: Requirements 24.1, 24.2**
  
  - [ ] 29.3 Implement performance monitoring
    - Track latency and error rates
    - Generate alerts for anomalies
    - _Requirements: 29.1, 29.2, 29.4_
  
  - [ ]* 29.4 Write property test for performance alert generation
    - **Property 49: Performance Alert Generation**
    - **Validates: Requirements 29.1**
  
  - [ ] 29.5 Optimize search performance
    - Add database indexes
    - Implement query optimization
    - Ensure sub-2-second response times
    - _Requirements: 27.6_
  
  - [ ]* 29.6 Write property test for search performance bounds
    - **Property 47: Search Performance Bounds**
    - **Validates: Requirements 27.6**

- [ ] 30. Implement sandbox environment and testing features
  - [ ] 30.1 Create environment toggle system
    - Support sandbox and live modes
    - Route to test gateways in sandbox
    - _Requirements: 26.1, 26.2_
  
  - [ ]* 30.2 Write property test for sandbox environment isolation
    - **Property 46: Sandbox Environment Isolation**
    - **Validates: Requirements 26.1**
  
  - [ ] 30.3 Implement webhook replay functionality
    - Store webhook history
    - Allow manual replay
    - _Requirements: 12.4, 12.6_
  
  - [ ] 30.4 Create API traffic inspector
    - Log all API requests/responses
    - Provide debugging interface
    - _Requirements: 11.5_

- [ ] 31. Implement multi-currency support
  - [ ] 31.1 Add currency conversion logic
    - Fetch exchange rates
    - Convert to base currency
    - _Requirements: 28.2, 28.3_
  
  - [ ]* 31.2 Write property test for multi-currency data preservation
    - **Property 48: Multi-Currency Data Preservation**
    - **Validates: Requirements 28.1, 28.6**
  
  - [ ] 31.3 Update dashboard to support multi-currency
    - Display in original and base currency
    - Support currency filtering
    - _Requirements: 28.4, 28.7_

- [ ] 32. Implement data export and reporting
  - [ ] 32.1 Create export functionality
    - Support CSV, Excel, JSON formats
    - Include all relevant fields
    - Process large exports asynchronously
    - _Requirements: 30.1, 30.2, 30.3_
  
  - [ ]* 32.2 Write property test for export data completeness
    - **Property 50: Export Data Completeness**
    - **Validates: Requirements 30.2**
  
  - [ ] 32.3 Implement scheduled exports
    - Allow recurring export configuration
    - Email reports automatically
    - _Requirements: 30.6_

- [ ] 33. Implement integration partner management
  - [ ] 33.1 Create partner configuration system
    - Store partner credentials in secrets backend
    - Configure endpoints and settings
    - _Requirements: 39.1, 39.2_
  
  - [ ]* 33.2 Write property test for partner API configuration usage
    - **Property 61: Partner API Configuration Usage**
    - **Validates: Requirements 39.3**
  
  - [ ] 33.3 Implement partner health monitoring
    - Track partner availability
    - Display health metrics
    - _Requirements: 39.4, 39.6_
  
  - [ ]* 33.4 Write property test for graceful partner disabling
    - **Property 62: Graceful Partner Disabling**
    - **Validates: Requirements 39.7**

- [ ] 34. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 35. Final integration and polish
  - [ ] 35.1 Implement comprehensive error handling
    - Standardize error response format
    - Implement circuit breakers
    - Add retry logic with idempotency
    - _Requirements: All (error handling)_
  
  - [ ] 35.2 Add monitoring and observability
    - Set up Prometheus metrics
    - Configure Grafana dashboards
    - Implement structured logging
    - _Requirements: All (monitoring)_
  
  - [ ] 35.3 Performance optimization
    - Add database indexes
    - Implement caching strategy
    - Optimize hot paths
    - _Requirements: All (performance)_
  
  - [ ] 35.4 Security hardening
    - Conduct security audit
    - Implement rate limiting
    - Add input validation
    - _Requirements: All (security)_
  
  - [ ] 35.5 Documentation
    - API documentation
    - Integration guides
    - Deployment documentation
    - _Requirements: All (documentation)_

- [ ] 36. Final checkpoint - Run full test suite
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional property-based tests that can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation throughout development
- Property tests validate universal correctness properties with minimum 100 iterations
- Unit tests validate specific examples, edge cases, and error conditions
- The implementation uses Go with hexagonal architecture for modularity
- All secrets are stored in configurable backend (PostgreSQL encrypted or OpenBao)
- The platform supports multi-market operations (Kenya, Rwanda, Tanzania, Burundi, Nigeria)
