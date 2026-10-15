<?php require 'vendor/autoload.php'; $app=require 'bootstrap/app.php'; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
// 本編の日替わりコーナー（ある曲のあと〜ある曲の前）の曲に、コーナーの何曲目かの番号（daily_note）を入れる。
// set_encore_slot_notes.php の本編版。例：602 FOUR-RE:ISM の「ゼロのままでいられたら」と「Subtitle」の間
// php set_setlist_slot_notes.php <tour_id> <row> '<コーナーの直前の曲>' '<コーナーの直後の曲>' [--prefix=X] [--dry]
//   コーナーの直後の曲が公演で違うときは '["曲名", "曲名"]' と JSON で渡す（どれかが最初に出たところまで。例：563）
//   本編にほかの日替わりの番号（1・2・3 など）がすでにあるときは --prefix で番号に前置きを付けて分ける（例：286 は --prefix=a で a1・a2・a3）
use App\Models\DbSetlist;

[$tourId, $row, $after, $before] = [(int) $argv[1], (int) $argv[2], $argv[3], $argv[4]];
$dry = in_array('--dry', $argv, true);
$prefix = '';
foreach ($argv as $a) if (str_starts_with($a, '--prefix=')) $prefix = substr($a, 9);
$songs = App\Models\DbSong::pluck('title', 'id');
$titleOf = fn ($it) => is_numeric($it['song'] ?? null) && isset($songs[(int) $it['song']]) ? $songs[(int) $it['song']] : (string) ($it['song'] ?? '');
$n = 0;
foreach (DbSetlist::where('tour_id', $tourId)->where('row', $row)->orderBy('order_no')->get() as $p) {
    $items = array_values($p->setlist ?? []);
    $titles = array_map($titleOf, $items);
    $start = array_search($after, $titles, true);
    $ends = str_starts_with($before, '[') ? json_decode($before, true) : [$before];
    $endHits = array_filter(array_map(fn ($b) => array_search($b, $titles, true), $ends), fn ($i) => $i !== false && $i > $start);
    $end = $endHits ? min($endHits) : false;
    if ($start === false || $end === false || $end <= $start) { echo "  #{$p->order_no} {$p->subtitle}：コーナーが見つからない\n"; continue; }
    $line = [];
    for ($i = $start + 1, $slot = 1; $i < $end; $i++, $slot++) {
        $items[$i]['daily_note'] = $prefix . $slot;
        $line[] = $titles[$i] . "[{$prefix}{$slot}]";
        $n++;
    }
    echo "  #{$p->order_no} {$p->subtitle}：" . implode(' / ', $line) . "\n";
    $p->setlist = $items;
    if (!$dry) $p->save();
}
echo "{$n}か所\n" . ($dry ? "（dry run）\n" : "（保存した）\n");
