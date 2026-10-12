<?php
$tables = ['students', 'violations', 'achievements', 'sanction_rules', 'discipline_events', 'counseling_records', 'schools', 'academic_years'];
foreach ($tables as $table) {
    echo "
--- $table ---
";
    print_r(Schema::getColumnListing($table));
}
