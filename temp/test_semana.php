<?php
echo "Aug 31: " . date('Y-W', strtotime('2026-08-31')) . " (day " . date('N', strtotime('2026-08-31')) . ")\n";
echo "Sep 1: " . date('Y-W', strtotime('2026-09-01')) . " (day " . date('N', strtotime('2026-09-01')) . ")\n";
echo "Sep 4: " . date('Y-W', strtotime('2026-09-04')) . " (day " . date('N', strtotime('2026-09-04')) . ")\n";
echo "Sep 7: " . date('Y-W', strtotime('2026-09-07')) . " (day " . date('N', strtotime('2026-09-07')) . ")\n";

// The original code used: DateTime::createFromFormat('Y-W', $semana)->modify('next monday -7 days')
// This was broken because createFromFormat fails.
// What the original code INTENDED: get the Monday of the given Y-W week.
// But since it never worked, we need to figure out the correct formula.

// date('Y') gives calendar year, date('W') gives ISO week
// Week 36 of 2026 spans Aug 31 - Sep 6 (Mon-Sun)
// So Monday of week 36 = Aug 31, 2026

// The formula: for a given Y-W string, find the Monday
// Using the ISO rule: Jan 4 is always in week 1
// Monday of week W = Jan4_Thursday + (W-1)*7 - 3

// But we need to use the RIGHT year. Since date('Y') can differ from ISO year,
// let me check...

// date('o') is ISO year
echo "\nISO year check:\n";
echo "Dec 31 2025: " . date('o', strtotime('2025-12-31')) . "\n";
echo "Jan 1 2026: " . date('o', strtotime('2026-01-01')) . "\n";

// For "2026-36": 2026 is the calendar year, 36 is ISO week
// We need to find Monday of ISO week 36 in year 2026
// Formula: Thursday of week 1 = Jan 4 of the ISO year
// But here the Y in Y-W is the calendar year from date('Y'), not necessarily the ISO year

// Simpler approach: just iterate and find it
// Or even simpler: use strtotime with a known date in that week

// The most reliable: use the week number to find the Monday
// Monday of ISO week W of year Y:
// Start with Jan 1 of year Y
// Find what day of week it is (1=Mon..7=Sun)
// If Jan 1 is Mon, week 1 starts Jan 1
// If Jan 1 is Tue, week 1 starts Dec 28 of prev year
// etc.

// Actually the simplest: use the "ISO week date" format with strtotime
// PHP supports "YYYY-WXX-D" format
$test = date('Y-m-d', strtotime('2026-W36-1')); // Monday of week 36
echo "\nstrtotime('2026-W36-1'): $test\n";
echo "Verify: " . date('Y-W', strtotime($test)) . "\n";
