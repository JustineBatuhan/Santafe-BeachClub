<?php

const SF_DEFAULT_PROPERTY_TIMEZONE = 'Asia/Manila';

function sf_normalize_timezone_identifier(?string $timezone): string
{
    $timezone = trim((string)$timezone);
    if ($timezone === '') {
        return SF_DEFAULT_PROPERTY_TIMEZONE;
    }

    static $valid_timezones = null;
    if ($valid_timezones === null) {
        $valid_timezones = array_flip(DateTimeZone::listIdentifiers());
    }

    return isset($valid_timezones[$timezone]) ? $timezone : SF_DEFAULT_PROPERTY_TIMEZONE;
}

function sf_get_supported_property_timezones(): array
{
    return [
        'Asia/Manila' => '(UTC+08:00) Asia/Manila',
        'Asia/Singapore' => '(UTC+08:00) Asia/Singapore',
        'Asia/Tokyo' => '(UTC+09:00) Asia/Tokyo',
        'Australia/Sydney' => '(UTC+10:00) Australia/Sydney',
        'Europe/London' => '(UTC+00:00) Europe/London',
        'America/Los_Angeles' => '(UTC-08:00/-07:00) America/Los_Angeles',
        'America/New_York' => '(UTC-05:00/-04:00) America/New_York',
        'UTC' => '(UTC+00:00) UTC',
    ];
}

function sf_get_property_timezone_setting(mysqli $conn): string
{
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'property_timezone' LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return sf_normalize_timezone_identifier($row['setting_value'] ?? null);
}

function sf_get_current_business_datetime(mysqli $conn): DateTimeImmutable
{
    $timezone = new DateTimeZone(sf_get_property_timezone_setting($conn));
    return new DateTimeImmutable('now', $timezone);
}
