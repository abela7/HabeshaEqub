-- Sample Members Data for HabeshaEqub Testing
-- Equib starts June 2024, £1000 monthly payment
-- Michael Werkneh already received payout (Position 1)

INSERT INTO members (
    member_id, 
    first_name, 
    last_name, 
    email, 
    phone, 
    password, 
    monthly_payment, 
    payout_position, 
    payout_month, 
    total_contributed, 
    has_received_payout,
    guarantor_first_name,
    guarantor_last_name, 
    guarantor_phone, 
    guarantor_email, 
    guarantor_relationship,
    is_active, 
    is_approved, 
    email_verified, 
    join_date, 
    last_login,
    notification_preferences, 
    notes,
    created_at, 
    updated_at
) VALUES 

-- 1. Michael Werkneh (Already received payout - June 2024)
('HEM-MW1', 'Michael', 'Werkneh', 'michael.werkneh@email.com', '+447123456789', 'MW123A', 1000.00, 1, '2024-06', 1000.00, 1, 'Sarah', 'Werkneh', '+447123456790', 'sarah.werkneh@email.com', 'Sister', 1, 1, 1, '2024-05-15', '2024-06-20 10:30:00', 'email,sms', 'First member - received June payout', NOW(), NOW()),

-- 2. Maeruf Nasir (Next payout - July 2024)
('HEM-MN2', 'Maeruf', 'Nasir', 'maeruf.nasir@email.com', '+447234567890', 'MN456B', 1000.00, 2, '2024-07', 1000.00, 0, 'Ahmed', 'Nasir', '+447234567891', 'ahmed.nasir@email.com', 'Brother', 1, 1, 1, '2024-05-15', '2024-06-18 14:15:00', 'email', 'Active member - good payment record', NOW(), NOW()),

-- 3. Teddy Elias (Payout - August 2024)
('HEM-TE3', 'Teddy', 'Elias', 'teddy.elias@email.com', '+447345678901', 'TE789C', 1000.00, 3, '2024-08', 1000.00, 0, 'Helen', 'Elias', '+447345678902', 'helen.elias@email.com', 'Mother', 1, 1, 1, '2024-05-15', '2024-06-19 16:45:00', 'email,sms', 'Reliable member', NOW(), NOW()),

-- 4. Kokit Gormesa (Payout - September 2024)
('HEM-KG4', 'Kokit', 'Gormesa', 'kokit.gormesa@email.com', '+447456789012', 'KG012D', 1000.00, 4, '2024-09', 1000.00, 0, 'Dawit', 'Gormesa', '+447456789013', 'dawit.gormesa@email.com', 'Husband', 1, 1, 1, '2024-05-15', '2024-06-17 12:20:00', 'sms', 'New member - very enthusiastic', NOW(), NOW()),

-- 5. Mahlet Ayalew (Last payout - October 2024)
('HEM-MA5', 'Mahlet', 'Ayalew', 'mahlet.ayalew@email.com', '+447567890123', 'MA345E', 1000.00, 5, '2024-10', 1000.00, 0, 'Bereket', 'Ayalew', '+447567890124', 'bereket.ayalew@email.com', 'Father', 1, 1, 1, '2024-05-15', '2024-06-21 09:10:00', 'email,sms', 'Last position - patient member', NOW(), NOW());

-- Additional sample payments data for Michael (who already received payout)
INSERT INTO payments (
    payment_id,
    member_id, 
    amount, 
    payment_month, 
    payment_date, 
    status, 
    payment_method, 
    verified_by_admin, 
    verification_date, 
    receipt_number, 
    notes,
    created_at, 
    updated_at
) VALUES 
('PAY-MW1-062024', 1, 1000.00, '2024-06', '2024-06-01 10:00:00', 'completed', 'bank_transfer', 1, '2024-06-01 10:30:00', 'RCP-MW1-001', 'June payment - on time', NOW(), NOW());

-- Sample payout record for Michael
INSERT INTO payouts (
    payout_id,
    member_id, 
    total_amount, 
    scheduled_date, 
    actual_payout_date, 
    status, 
    payout_method, 
    processed_by_admin_id, 
    admin_fee, 
    net_amount, 
    transaction_reference, 
    receipt_issued, 
    payout_notes,
    created_at, 
    updated_at
) VALUES 
('PAYOUT-MW1-062024', 1, 5000.00, '2024-06-15', '2024-06-15 14:00:00', 'completed', 'bank_transfer', 1, 50.00, 4950.00, 'TXN-MW1-20240615', 1, 'First equib payout - Michael Werkneh - June 2024. Total collected: £5000, Admin fee: £50, Net payout: £4950', NOW(), NOW()); 