<?php

require 'vendor/autoload.php';
$igo = new Igo\Tagger();

// 文部科学省 小学校学習指導要領（平成29年告示）別表「学年別漢字配当表」（2020年度～）
// https://www.mext.go.jp/content/20230120-mxt_kyoiku02-100002604_01.pdf
$grades = json_decode(file_get_contents(__DIR__ . '/gakunenKanji.json'), true, 512, JSON_THROW_ON_ERROR);

/*
* 学年指定で習得漢字の一覧を返す
*
* @param int $grade 学年指定
* @param array $grades 学年ごとの漢字リスト
* @return array 習得漢字
*/
function getKanji(int $grade, array $grades): array {
    $result = [];
    for ($i = 1; $i <= $grade; $i++) {
        $result = array_merge($result, $grades[$i]);
    }
    return $result;
}

/*
* 漢字の判定
*
* @param str $s 文字1文字
* @return bool 漢字:true 漢字以外:false
*/
function isKanji(string $s): bool {
    return (bool) preg_match('/^[一-龠]$/u', $s);
}

// 文章指定
$text = "祇園精舎の鐘の声、諸行無常の響きあり。娑羅双樹の花の色、盛者必衰の理をあらはす。";

// igo-phpで文章を分割
$igoData = $igo->parse($text);

// 学年指定で漢字取得
$kanjiArray = getKanji(1, $grades);

// 分割された文字単位で修得漢字を判定し、文章の生成
$resultText = "";
foreach ($igoData as $value) {
    $feature = $value->feature;
    $learningFlg = true;

    // 1文字ずづ取り出し
    foreach (mb_str_split($feature[6]) as $str) {
        // 漢字かどうかを判別
        if (isKanji($str) === true) {
            // 習得漢字に含まれているかどうかをチェック
            if (array_search($str, $kanjiArray) === false) {
                // 1単語の中に未習得の漢字が含まれていたらfalseを指定
                $learningFlg = false;
            }
        }
    }

    // テキストの結合
    if ($learningFlg === true) {
        // 漢字
        $resultText .= $value->surface;
    } else {
        // カナ
        $resultText .= $feature[7];
    }
}

// カタカナをひらがなに変換して表示
print_r(mb_convert_kana($resultText, "c", "utf-8"));
