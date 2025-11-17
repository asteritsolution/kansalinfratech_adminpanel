<?php
/**
 * Common Helper Functions
 * Include this file once in your PHP files to use these helper functions
 */

if (!function_exists('getInitials')) {
    /**
     * Get initials from a name
     * @param string $name Full name
     * @return string Two-letter initials
     */
    function getInitials($name) {
        $words = explode(' ', $name);
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }
        return substr($initials, 0, 2);
    }
}

if (!function_exists('formatDate')) {
    /**
     * Format date to readable format
     * @param string $date Date string
     * @return string Formatted date or '-'
     */
    function formatDate($date) {
        if (empty($date) || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') {
            return '-';
        }
        return date('d M Y', strtotime($date));
    }
}

if (!function_exists('formatDateTime')) {
    /**
     * Format datetime to readable format
     * @param string $datetime Datetime string
     * @return string Formatted datetime or '-'
     */
    function formatDateTime($datetime) {
        if (empty($datetime) || $datetime == '0000-00-00 00:00:00') {
            return '-';
        }
        return date('d M Y - h:i A', strtotime($datetime));
    }
}

if (!function_exists('getStatusBadgeClass')) {
    /**
     * Get CSS class for status badge
     * @param string $status Status string
     * @return string Badge CSS class
     */
    function getStatusBadgeClass($status) {
        $status = strtolower(trim($status));
        
        // Lead statuses
        if (in_array($status, ['active', 'qualified', 'site visit', 'converted', 'closed won'])) {
            return 'badge-success';
        } elseif (in_array($status, ['follow up', 'new', 'pending'])) {
            return 'badge-warning';
        } elseif (in_array($status, ['closed lost', 'suspended', 'inactive'])) {
            return 'badge-danger';
        }
        
        // User statuses
        if ($status == 'active') {
            return 'badge-success';
        } elseif ($status == 'pending') {
            return 'badge-warning';
        } elseif ($status == 'suspended') {
            return 'badge-danger';
        }
        
        return 'badge-warning';
    }
}

if (!function_exists('formatCurrency')) {
    /**
     * Format currency amount
     * @param float $amount Amount in rupees
     * @return string Formatted currency string
     */
    function formatCurrency($amount) {
        if ($amount >= 10000000) {
            return '₹' . number_format($amount / 10000000, 2) . ' Cr';
        } elseif ($amount >= 100000) {
            return '₹' . number_format($amount / 100000, 2) . ' L';
        } else {
            return '₹' . number_format($amount);
        }
    }
}
?>

