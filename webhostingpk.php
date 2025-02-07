<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

// Logging function
function log_message($message) {
    file_put_contents('webhostingpk.log', date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
}

// Function to save nameservers to a file
function save_nameservers_to_file($domain, $ns1, $ns2) {
    $file = 'nameservers.json';
    $data = [];

    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
    }

    $data[$domain] = ['ns1' => $ns1, 'ns2' => $ns2];
    file_put_contents($file, json_encode($data));
}

// Function to get nameservers from a file
function get_nameservers_from_file($domain) {
    $file = 'nameservers.json';

    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if (isset($data[$domain])) {
            return $data[$domain];
        }
    }

    return null;
}

function webhostingpk_getConfigArray() {
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'WebHostingPk Registrar'
        ],
        'AdminEmail' => [
            'Type' => 'text',
            'Size' => '50',
            'Description' => 'Email for notifications'
        ],
        'ResellerEmail' => [
            'Type' => 'text',
            'Size' => '50',
            'Description' => 'Reseller Email for notifications'
        ],
    ];
}

function webhostingpk_GetNameservers($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $saved_nameservers = get_nameservers_from_file($domain);

    if ($saved_nameservers) {
        return $saved_nameservers;
    }

    return [
        'ns1' => 'ns1.webhostingpk.com',
        'ns2' => 'ns2.webhostingpk.com',
    ];
}

function webhostingpk_SaveNameservers($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $ns1 = filter_var($params['ns1'], FILTER_SANITIZE_STRING);
    $ns2 = filter_var($params['ns2'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);

    $subject = "Nameserver Change Request for $domain";
    $message = "
        Hello,<br><br>
        We have received a request to change the nameservers for your domain: <strong>$domain</strong>.<br><br>
        <strong>New Nameservers:</strong><br>
        - NS1: $ns1<br>
        - NS2: $ns2<br><br>
        If you did not request this change, please contact us immediately.<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    // Save the nameservers to a file
    save_nameservers_to_file($domain, $ns1, $ns2);

    log_message("Nameserver change request for $domain");
    return ['success' => true];
}

function webhostingpk_GetEPPCode($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);

    $subject = "EPP Code Request for $domain";
    $message = "
        Hello,<br><br>
        A request has been made for the EPP code for your domain: <strong>$domain</strong>.<br><br>
        Please find the EPP code below:<br>
        <strong>EPP Code:</strong> [Generated EPP Code]<br><br>
        If you have any questions, feel free to reach out.<br><br>
        Best regards,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("EPP code request for $domain");
    return ['eppcode' => 'Request sent to admin'];
}

function webhostingpk_GetRegistrarLock($params) {
    return "locked";
}

function webhostingpk_SaveRegistrarLock($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $lockstatus = filter_var($params['lockenabled'], FILTER_VALIDATE_BOOLEAN);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);

    $status = $lockstatus ? "Lock" : "Unlock";
    $subject = "Domain $status Request for $domain";
    $message = "
        Hello,<br><br>
        A request has been made to $status your domain: <strong>$domain</strong>.<br><br>
        If you did not initiate this request, please contact us immediately.<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("$status request for $domain");
    return ['success' => true];
}

function webhostingpk_SendEmailNotification($params, $type, $details = []) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);

    $subject = "$type Request for $domain";
    $message = "
        Hello,<br><br>
        The $type request for your domain: <strong>$domain</strong> has been processed.<br><br>
    ";

    if (!empty($details)) {
        $message .= "<strong>Details:</strong><br>";
        foreach ($details as $key => $value) {
            if (is_array($value)) {
                $message .= "$key:<br>";
                foreach ($value as $item) {
                    $message .= "  - $item<br>";
                }
            } else {
                $message .= "$key: $value<br>";
            }
        }
    }

    $message .= "<br>Thank you,<br>The Web Hosting PK Team";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    return true;
}

// Domain Management Functions
function webhostingpk_RequestDelete($params) {
    webhostingpk_SendEmailNotification($params, 'Domain Deletion');
    log_message("Domain deletion request for " . $params['sld'] . '.' . $params['tld']);
    return ['success' => true];
}

function webhostingpk_ReleaseDomain($params) {
    webhostingpk_SendEmailNotification($params, 'Domain Release', [
        'New Registrar' => $params['transfertag']
    ]);
    log_message("Domain release request for " . $params['sld'] . '.' . $params['tld']);
    return ['success' => true];
}

function webhostingpk_IDProtectToggle($params) {
    webhostingpk_SendEmailNotification($params, 'ID Protection', [
        'Protect' => $params['protectenable'] ? 'Enable' : 'Disable'
    ]);
    log_message("ID protection toggle request for " . $params['sld'] . '.' . $params['tld']);
    return ['success' => true];
}

function webhostingpk_Sync($params) {
    return [
        'expirydate' => date('Y-m-d', strtotime('+1 year')),
        'active' => true
    ];
}

function webhostingpk_TransferSync($params) {
    return [
        'completed' => false,
        'expirydate' => '',
    ];
}

function webhostingpk_RenewDomain($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);

    $subject = "Domain Renewal Request for $domain";
    $message = "
        Hello,<br><br>
        A request has been made to renew your domain: <strong>$domain</strong>.<br><br>
        If you did not initiate this request, please contact us immediately.<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("Domain renewal request for $domain");
    return ['success' => true];
}

function webhostingpk_UpdateContactInfo($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);
    $contactInfo = $params['contactinfo'];

    $subject = "Contact Information Update for $domain";
    $message = "
        Hello,<br><br>
        The contact information for your domain: <strong>$domain</strong> has been updated.<br><br>
        <strong>New Contact Information:</strong><br>
    ";

    foreach ($contactInfo as $key => $value) {
        $message .= "$key: $value<br>";
    }

    $message .= "<br>Thank you,<br>The Web Hosting PK Team";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("Contact information update for $domain");
    return ['success' => true];
}

// DNS Management Functions
function webhostingpk_GetDNS($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    // Return current DNS records - implement actual fetching logic here
    return [
        [
            'hostname' => '@',
            'type' => 'A',
            'address' => '192.0.2.1',
            'priority' => '',
        ],
        [
            'hostname' => 'www',
            'type' => 'CNAME',
            'address' => $domain,
            'priority' => '',
        ],
    ];
}

function webhostingpk_SaveDNS($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $dnsrecords = $params['dnsrecords'];
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);

    $details = ['DNS Records' => []];
    foreach ($dnsrecords as $record) {
        $details['DNS Records'][] = sprintf(
            "%s %s record: %s -> %s %s",
            $record['hostname'],
            $record['type'],
            $record['address'],
            isset($record['priority']) ? "Priority: {$record['priority']}" : ''
        );
    }

    webhostingpk_SendEmailNotification($params, 'DNS Update', $details);
    log_message("DNS update request for $domain");
    return ['success' => true];
}

function webhostingpk_AddDNSZone($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);

    webhostingpk_SendEmailNotification($params, 'Add DNS Zone', [
        'Domain' => $domain,
        'Action' => 'Create new DNS zone'
    ]);
    log_message("Add DNS zone request for $domain");
    return ['success' => true];
}

function webhostingpk_DeleteDNSZone($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);

    webhostingpk_SendEmailNotification($params, 'Delete DNS Zone', [
        'Domain' => $domain,
        'Action' => 'Remove DNS zone'
    ]);
    log_message("Delete DNS zone request for $domain");
    return ['success' => true];
}

function webhostingpk_EnableDNSSEC($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);

    webhostingpk_SendEmailNotification($params, 'Enable DNSSEC', [
        'Domain' => $domain,
        'Action' => 'Enable DNSSEC'
    ]);
    log_message("Enable DNSSEC request for $domain");
    return ['success' => true];
}

function webhostingpk_DisableDNSSEC($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);

    webhostingpk_SendEmailNotification($params, 'Disable DNSSEC', [
        'Domain' => $domain,
        'Action' => 'Disable DNSSEC'
    ]);
    log_message("Disable DNSSEC request for $domain");
    return ['success' => true];
}

function webhostingpk_getDNSRecordTypes() {
    return [
        'A' => 'A',
        'AAAA' => 'AAAA',
        'CNAME' => 'CNAME',
        'MX' => 'MX',
        'TXT' => 'TXT',
        'NS' => 'NS',
        'SRV' => 'SRV',
        'CAA' => 'CAA',
    ];
}

// Function to handle domain order
function webhostingpk_DomainOrder($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);

    $subject = "New Domain Order for $domain";
    $message = "
        Hello,<br><br>
        A new domain order has been placed for: <strong>$domain</strong>.<br><br>
        Please review the order details and process accordingly.<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("New domain order for $domain");
    return ['success' => true];
}

// Function to handle domain registration
function webhostingpk_RegisterDomain($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);

    $subject = "Domain Registration Confirmation for $domain";
    $message = "
        Hello,<br><br>
        The domain <strong>$domain</strong> has been successfully registered.<br><br>
        Please review the registration details and ensure everything is correct.<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("Domain registration confirmation for $domain");
    return ['success' => true];
}

// Function to handle domain transfer
function webhostingpk_TransferDomain($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);

    $subject = "Domain Transfer Confirmation for $domain";
    $message = "
        Hello,<br><br>
        The domain <strong>$domain</strong> has been successfully transferred to our registrar.<br><br>
        Please review the transfer details and ensure everything is correct.<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("Domain transfer confirmation for $domain");
    return ['success' => true];
}

// Function to handle domain order cancellation
function webhostingpk_CancelDomainOrder($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);

    $subject = "Domain Order Cancellation for $domain";
    $message = "
        Hello,<br><br>
        The domain order for <strong>$domain</strong> has been cancelled.<br><br>
        Please review the cancellation details and ensure everything is correct.<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("Domain order cancellation for $domain");
    return ['success' => true];
}

// Function to handle domain suspension
function webhostingpk_SuspendDomain($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);

    $subject = "Domain Suspension for $domain";
    $message = "
        Hello,<br><br>
        The domain <strong>$domain</strong> has been suspended.<br><br>
        Please review the suspension details and ensure everything is correct.<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("Domain suspension for $domain");
    return ['success' => true];
}

// Function to handle domain expiry notification
function webhostingpk_DomainExpiryNotification($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);
    $expiryDate = $params['expirydate'];

    $subject = "Domain Expiry Notification for $domain";
    $message = "
        Hello,<br><br>
        The domain <strong>$domain</strong> is about to expire on <strong>$expiryDate</strong>.<br><br>
        Please renew your domain to avoid any disruption in services.<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("Domain expiry notification for $domain");
    return ['success' => true];
}

// Function to handle domain auto-renewal notification
function webhostingpk_DomainAutoRenewalNotification($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);
    $renewalStatus = $params['renewalstatus'];

    $subject = "Domain Auto-Renewal Notification for $domain";
    $message = "
        Hello,<br><br>
        The domain <strong>$domain</strong> has been set to auto-renew.<br><br>
        Auto-renewal status: <strong>$renewalStatus</strong>.<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("Domain auto-renewal notification for $domain");
    return ['success' => true];
}

// Function to handle domain status change notification
function webhostingpk_DomainStatusChangeNotification($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);
    $newStatus = $params['newstatus'];

    $subject = "Domain Status Change Notification for $domain";
    $message = "
        Hello,<br><br>
        The status of the domain <strong>$domain</strong> has been changed to <strong>$newStatus</strong>.<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("Domain status change notification for $domain");
    return ['success' => true];
}

// Function to handle Whois privacy notification
function webhostingpk_WhoisPrivacyNotification($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);
    $privacyStatus = $params['privacystatus'] ? 'Enabled' : 'Disabled';

    $subject = "Whois Privacy Notification for $domain";
    $message = "
        Hello,<br><br>
        The Whois privacy for the domain <strong>$domain</strong> has been <strong>$privacyStatus</strong>.<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("Whois privacy notification for $domain");
    return ['success' => true];
}

// Function to handle domain ownership verification
function webhostingpk_DomainOwnershipVerification($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $customerEmail = filter_var($params['CustomerEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);

    $subject = "Domain Ownership Verification for $domain";
    $message = "
        Hello,<br><br>
        Please verify your ownership of the domain <strong>$domain</strong> by clicking the link below:<br><br>
        <a href='VERIFICATION_LINK'>Verify Ownership</a><br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($customerEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("Domain ownership verification for $domain");
    return ['success' => true];
}

// Function to handle domain transfer failure notification
function webhostingpk_DomainTransferFailureNotification($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);

    $subject = "Domain Transfer Failure for $domain";
    $message = "
        Hello,<br><br>
        The transfer of the domain <strong>$domain</strong> has failed.<br><br>
        Please review the details and take the necessary actions.<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("Domain transfer failure notification for $domain");
    return ['success' => true];
}

// Function to handle domain renewal reminder
function webhostingpk_DomainRenewalReminder($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);
    $renewalDate = $params['renewaldate'];

    $subject = "Domain Renewal Reminder for $domain";
    $message = "
        Hello,<br><br>
        This is a reminder that your domain <strong>$domain</strong> is due for renewal on <strong>$renewalDate</strong>.<br><br>
        Please renew your domain to avoid any disruption in services.<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("Domain renewal reminder for $domain");
    return ['success' => true];
}

// Function to handle DNS configuration change notification
function webhostingpk_DNSConfigurationChangeNotification($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);

    $subject = "DNS Configuration Change Notification for $domain";
    $message = "
        Hello,<br><br>
        The DNS configuration for the domain <strong>$domain</strong> has been changed.<br><br>
        Please review the changes and ensure everything is correct.<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("DNS configuration change notification for $domain");
    return ['success' => true];
}

// Function to handle domain contact information verification
function webhostingpk_DomainContactInfoVerification($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $customerEmail = filter_var($params['CustomerEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);

    $subject = "Domain Contact Information Verification for $domain";
    $message = "
        Hello,<br><br>
        Please verify your contact information for the domain <strong>$domain</strong> by clicking the link below:<br><br>
        <a href='VERIFICATION_LINK'>Verify Contact Information</a><br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($customerEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("Domain contact information verification for $domain");
    return ['success' => true];
}

// Function to handle domain security alert
function webhostingpk_DomainSecurityAlert($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);
    $alertDetails = $params['alertdetails'];

    $subject = "Security Alert for $domain";
    $message = "
        Hello,<br><br>
        There has been a security alert for the domain <strong>$domain</strong>.<br><br>
        <strong>Alert Details:</strong><br>
        $alertDetails<br><br>
        Please take immediate action to resolve this issue.<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("Security alert for $domain");
    return ['success' => true];
}

// Function to handle domain usage statistics
function webhostingpk_DomainUsageStatistics($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);
    $statistics = $params['statistics'];

    $subject = "Domain Usage Statistics for $domain";
    $message = "
        Hello,<br><br>
        Here are the usage statistics for the domain <strong>$domain</strong>:<br><br>
        <strong>Statistics:</strong><br>
        $statistics<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("Domain usage statistics for $domain");
    return ['success' => true];
}

// Function to handle domain payment reminder
function webhostingpk_DomainPaymentReminder($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $customerEmail = filter_var($params['CustomerEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);
    $paymentDate = $params['paymentdate'];

    $subject = "Domain Payment Reminder for $domain";
    $message = "
        Hello,<br><br>
        This is a reminder that your payment for the domain <strong>$domain</strong> is due on <strong>$paymentDate</strong>.<br><br>
        Please make the payment to avoid any disruption in services.<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($customerEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("Domain payment reminder for $domain");
    return ['success' => true];
}

// Function to handle domain backup notification
function webhostingpk_DomainBackupNotification($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);
    $backupStatus = $params['backupstatus'];

    $subject = "Domain Backup Notification for $domain";
    $message = "
        Hello,<br><br>
        The backup for the domain <strong>$domain</strong> has been <strong>$backupStatus</strong>.<br><br>
        Please review the backup details and ensure everything is correct.<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("Domain backup notification for $domain");
    return ['success' => true];
}

// Function to handle SSL certificate expiry notification
function webhostingpk_SSLCertificateExpiryNotification($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);
    $expiryDate = $params['expirydate'];

    $subject = "SSL Certificate Expiry Notification for $domain";
    $message = "
        Hello,<br><br>
        The SSL certificate for the domain <strong>$domain</strong> is about to expire on <strong>$expiryDate</strong>.<br><br>
        Please renew your SSL certificate to avoid any disruption in services.<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("SSL certificate expiry notification for $domain");
    return ['success' => true];
}

// Function to handle domain traffic monitoring
function webhostingpk_DomainTrafficMonitoring($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);
    $trafficDetails = $params['trafficdetails'];

    $subject = "Domain Traffic Monitoring Report for $domain";
    $message = "
        Hello,<br><br>
        Here is the traffic report for the domain <strong>$domain</strong>:<br><br>
        <strong>Traffic Details:</strong><br>
        $trafficDetails<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("Domain traffic monitoring report for $domain");
    return ['success' => true];
}

// Function to handle domain health check
function webhostingpk_DomainHealthCheck($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);
    $healthStatus = $params['healthstatus'];

    $subject = "Domain Health Check for $domain";
    $message = "
        Hello,<br><br>
        The health status of the domain <strong>$domain</strong> is <strong>$healthStatus</strong>.<br><br>
        Please review the health details and ensure everything is correct.<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("Domain health check for $domain");
    return ['success' => true];
}

// Function to handle domain service upgrade notification
function webhostingpk_DomainServiceUpgradeNotification($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);
    $upgradeDetails = $params['upgradedetails'];

    $subject = "Domain Service Upgrade Notification for $domain";
    $message = "
        Hello,<br><br>
        The domain <strong>$domain</strong> has been upgraded with the following details:<br><br>
        <strong>Upgrade Details:</strong><br>
        $upgradeDetails<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("Domain service upgrade notification for $domain");
    return ['success' => true];
}

// Function to handle domain abuse report
function webhostingpk_DomainAbuseReport($params) {
    $domain = filter_var($params['sld'] . '.' . $params['tld'], FILTER_SANITIZE_STRING);
    $adminEmail = filter_var($params['AdminEmail'], FILTER_SANITIZE_EMAIL);
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);
    $reportDetails = $params['reportdetails'];

    $subject = "Domain Abuse Report for $domain";
    $message = "
        Hello,<br><br>
        We have received an abuse report for the domain <strong>$domain</strong>.<br><br>
        <strong>Report Details:</strong><br>
        $reportDetails<br><br>
        Please review the report and take the necessary actions.<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($adminEmail, $subject, $message, $headers);

    if ($resellerEmail) {
        mail($resellerEmail, $subject, $message, $headers);
    }

    log_message("Domain abuse report for $domain");
    return ['success' => true];
}
function webhostingpk_ResellerCommissionTracking($params) {
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);
    $commissionDetails = $params['commissiondetails'];

    $subject = "Reseller Commission Tracking Report";
    $message = "
        Hello,<br><br>
        Here is your commission tracking report:<br><br>
        <strong>Commission Details:</strong><br>
        $commissionDetails<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($resellerEmail, $subject, $message, $headers);

    log_message("Reseller commission tracking report for $resellerEmail");
    return ['success' => true];
}
function webhostingpk_ResellerPerformanceReport($params) {
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);
    $performanceDetails = $params['performancedetails'];

    $subject = "Reseller Performance Report";
    $message = "
        Hello,<br><br>
        Here is your performance report:<br><br>
        <strong>Performance Details:</strong><br>
        $performanceDetails<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($resellerEmail, $subject, $message, $headers);

    log_message("Reseller performance report for $resellerEmail");
    return ['success' => true];
}
function webhostingpk_ResellerPromotions($params) {
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);
    $promotionDetails = $params['promotiondetails'];

    $subject = "Reseller Promotions and Discounts";
    $message = "
        Hello,<br><br>
        Here are the details of the current promotions and discounts:<br><br>
        <strong>Promotion Details:</strong><br>
        $promotionDetails<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($resellerEmail, $subject, $message, $headers);

    log_message("Reseller promotions and discounts for $resellerEmail");
    return ['success' => true];
}
function webhostingpk_ResellerSupportTickets($params) {
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);
    $ticketDetails = $params['ticketdetails'];

    $subject = "Reseller Support Ticket Update";
    $message = "
        Hello,<br><br>
        Here is an update on your support ticket:<br><br>
        <strong>Ticket Details:</strong><br>
        $ticketDetails<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($resellerEmail, $subject, $message, $headers);

    log_message("Reseller support ticket update for $resellerEmail");
    return ['success' => true];
}
function webhostingpk_ResellerBilling($params) {
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);
    $billingDetails = $params['billingdetails'];

    $subject = "Reseller Billing and Invoicing";
    $message = "
        Hello,<br><br>
        Here are the details of your billing and invoicing:<br><br>
        <strong>Billing Details:</strong><br>
        $billingDetails<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($resellerEmail, $subject, $message, $headers);

    log_message("Reseller billing and invoicing for $resellerEmail");
    return ['success' => true];
}
function webhostingpk_ResellerAnalytics($params) {
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);
    $analyticsDetails = $params['analyticsdetails'];

    $subject = "Reseller Analytics Report";
    $message = "
        Hello,<br><br>
        Here is your analytics report:<br><br>
        <strong>Analytics Details:</strong><br>
        $analyticsDetails<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($resellerEmail, $subject, $message, $headers);

    log_message("Reseller analytics report for $resellerEmail");
    return ['success' => true];
}
function webhostingpk_ResellerSalesReport($params) {
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);
    $salesDetails = $params['salesdetails'];

    $subject = "Reseller Sales Report";
    $message = "
        Hello,<br><br>
        Here is your sales report:<br><br>
        <strong>Sales Details:</strong><br>
        $salesDetails<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($resellerEmail, $subject, $message, $headers);

    log_message("Reseller sales report for $resellerEmail");
    return ['success' => true];
}
function webhostingpk_ResellerPaymentTracking($params) {
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);
    $paymentDetails = $params['paymentdetails'];

    $subject = "Reseller Payment Tracking Report";
    $message = "
        Hello,<br><br>
        Here is your payment tracking report:<br><br>
        <strong>Payment Details:</strong><br>
        $paymentDetails<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($resellerEmail, $subject, $message, $headers);

    log_message("Reseller payment tracking report for $resellerEmail");
    return ['success' => true];
}
function webhostingpk_ResellerSecurityAlert($params) {
    $resellerEmail = filter_var($params['ResellerEmail'], FILTER_SANITIZE_EMAIL);
    $alertDetails = $params['alertdetails'];

    $subject = "Security Alert for Your Domains";
    $message = "
        Hello,<br><br>
        There has been a security alert for your domains:<br><br>
        <strong>Alert Details:</strong><br>
        $alertDetails<br><br>
        Please take immediate action to resolve this issue.<br><br>
        Thank you,<br>
        The Web Hosting PK Team
    ";

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    mail($resellerEmail, $subject, $message, $headers);

    log_message("Security alert for $resellerEmail");
    return ['success' => true];
}
