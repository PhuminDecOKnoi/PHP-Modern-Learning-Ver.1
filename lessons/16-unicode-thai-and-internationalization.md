# 16 — Unicode, Thai Language and Internationalization

## บทนำ

ระบบภาษาไทยต้องจัดการมากกว่าการตั้ง charset เป็น UTF-8 เพราะข้อความหนึ่งตัวที่ผู้ใช้มองเห็นอาจประกอบด้วย Unicode code points หลายตัว การนับความยาว การตัดข้อความ การค้นหา การเรียงลำดับ วันที่ สกุลเงิน และ timezone จึงต้องใช้ API ที่ถูกต้อง

บทนี้มุ่งสร้างความเข้าใจด้าน Unicode และ Internationalization (i18n) สำหรับระบบ HR, Labour Compliance และเว็บแอปหลายภาษา โดยแยก i18n ออกจาก localization (l10n) และ translation content อย่างชัดเจน

---

## ผลลัพธ์การเรียนรู้

- อธิบาย UTF-8, code point, grapheme cluster และ normalization ได้
- ใช้ `mbstring`, `grapheme_*` และ `Normalizer`
- จัดรูปแบบวันที่ ตัวเลข และสกุลเงินด้วย `intl`
- จัดการ timezone อย่างถูกต้อง
- ออกแบบ translation keys และ locale fallback
- เข้าใจข้อจำกัดการเรียงภาษาไทย
- ป้องกัน Unicode-related validation และ security bugs

---

## 1. UTF-8 ไม่เท่ากับ Character Count

```php
$text = 'กำลังใจ';

echo strlen($text);     // จำนวน bytes
echo mb_strlen($text);  // จำนวน Unicode characters ตาม encoding
echo grapheme_strlen($text); // จำนวน grapheme clusters
```

### ความหมาย

- Byte — หน่วยข้อมูล
- Code point — ค่า Unicode
- Grapheme cluster — สิ่งที่ผู้ใช้มองเป็นหนึ่งตัวอักษร

สำหรับ UI limit ควรพิจารณา grapheme มากกว่า byte count ส่วน storage limit ต้องพิจารณา bytes ด้วย

---

## 2. mbstring Baseline

ตรวจ extension:

```bash
php --ri mbstring
php --ri intl
```

ตั้งค่า internal encoding อย่างชัดเจนเมื่อจำเป็น:

```php
mb_internal_encoding('UTF-8');
```

ตัดข้อความ:

```php
$preview = mb_substr($text, 0, 100, 'UTF-8');
```

Case conversion:

```php
$normalizedEmail = mb_strtolower($email, 'UTF-8');
```

อย่าพึ่ง `strtolower()` กับข้อความ non-ASCII

---

## 3. Grapheme-safe Truncation

```php
function truncateGrapheme(string $text, int $limit): string
{
    if (grapheme_strlen($text) <= $limit) {
        return $text;
    }

    $cut = grapheme_substr($text, 0, $limit);

    if ($cut === false) {
        throw new RuntimeException('Unable to truncate text.');
    }

    return $cut . '…';
}
```

เหมาะกับชื่อบุคคล ข้อความ UI และ social content ที่มี emoji/combining marks

---

## 4. Unicode Normalization

ข้อความที่มองเหมือนกันอาจมี code-point sequence ต่างกัน

```php
use Normalizer;

$normalized = Normalizer::normalize($input, Normalizer::FORM_C);

if ($normalized === false) {
    throw new RuntimeException('Unicode normalization failed.');
}
```

### ใช้เมื่อ

- เปรียบเทียบ identifier ที่รับจากภายนอก
- Search indexing
- Deduplication
- Import data จากหลายระบบ

### ข้อควรระวัง

Normalization ไม่แทน business validation และไม่ควรเปลี่ยนข้อมูลต้นฉบับโดยไม่มี policy

---

## 5. Collation และการเรียงภาษาไทย

อย่าใช้ binary sort สำหรับชื่อภาษาไทยเมื่อผู้ใช้คาดหวังลำดับตามภาษา

```php
$collator = new Collator('th_TH');
$names = ['กมล', 'ขจร', 'อรุณ'];
$collator->sort($names);
```

การเรียงใน PHP และ database ต้องมี policy สอดคล้องกัน โดยตรวจ collation ของ MySQL/PostgreSQL/OS ICU version ด้วย

---

## 6. Number และ Currency Formatting

```php
$formatter = new NumberFormatter(
    'th_TH',
    NumberFormatter::CURRENCY,
);

$output = $formatter->formatCurrency(12500.50, 'THB');
```

ห้ามสร้างสกุลเงินด้วยการต่อ `'บาท'` และ `number_format()` เพียงอย่างเดียวในระบบหลาย locale

สำหรับข้อมูลธุรกิจให้เก็บจำนวนเงินเป็น decimal/จำนวนหน่วยย่อย ไม่เก็บ formatted string

---

## 7. Date and Time Formatting

```php
$formatter = new IntlDateFormatter(
    'th_TH',
    IntlDateFormatter::LONG,
    IntlDateFormatter::SHORT,
    'Asia/Bangkok',
    IntlDateFormatter::GREGORIAN,
);

$date = new DateTimeImmutable('2026-08-01T08:00:00+07:00');
echo $formatter->format($date);
```

### Storage rule

- เก็บ event timestamp เป็น UTC
- เก็บ timezone แยกเมื่อมีความหมายธุรกิจ
- แปลงเป็น locale/timezone ตอนแสดงผล
- ใช้ `DATE` สำหรับวันโดยไม่มีเวลา เช่น วันเกิด

---

## 8. พ.ศ. และ ค.ศ.

ระบบไทยต้องแยก:

- ปีที่ใช้แสดงผล
- ปีที่ใช้คำนวณและจัดเก็บ
- Calendar system

ไม่ควรบวก 543 ด้วยมือทั่ว codebase ควรสร้าง DateFormatter/Calendar service ที่มี test ชัดเจน

```php
interface LocalizedDateFormatter
{
    public function formatDate(
        DateTimeInterface $date,
        string $locale,
        string $timezone,
    ): string;
}
```

---

## 9. Translation Architecture

ใช้ stable translation keys:

```php
$message = $translator->translate(
    'employee.registration.success',
    ['employeeCode' => $employeeCode],
    $locale,
);
```

ไม่ควรใช้ข้อความภาษาอังกฤษเต็มประโยคเป็น key หากระบบต้องดูแลระยะยาว

โครงสร้าง:

```text
translations/
├── en/
│   └── messages.php
└── th/
    └── messages.php
```

### Policy

- Default locale
- Supported locales
- Fallback locale
- Missing key handling
- Placeholder escaping
- Translation review process

---

## 10. Pluralization

ภาษาแต่ละภาษามีกฎ plural ต่างกัน ไม่ควรเขียน:

```php
$count . ' item' . ($count > 1 ? 's' : '');
```

ใช้ MessageFormatter/ICU message syntax หรือ translation library ที่รองรับ plural rules

---

## 11. Input Validation

ตรวจทั้ง:

- Byte size
- Grapheme length
- Allowed scripts ตาม business requirement
- Control characters
- Normalization policy
- Invisible characters

อย่าห้าม Unicode ทั้งหมดเพียงเพื่อแก้ validation ง่าย ๆ เพราะจะทำให้ชื่อบุคคลจริงใช้งานไม่ได้

---

## 12. Security Considerations

Unicode threats:

- Homoglyph/confusable characters
- Bidirectional controls
- Invisible separators
- Normalization mismatch
- Identifier spoofing

สำหรับ username/domain/approval code ที่มีความเสี่ยงสูง ควรกำหนด character policy แคบกว่าชื่อแสดงผล

Display name กับ login identifier ไม่ควรใช้ validation policy เดียวกัน

---

## 13. Search ภาษาไทย

การค้นหาภาษาไทยต้องพิจารณา:

- Word segmentation
- Normalization
- Stop words
- Synonyms
- Typo tolerance
- Database collation
- Search engine/tokenizer

`LIKE '%คำ%'` อาจเพียงพอในข้อมูลเล็ก แต่ไม่ใช่ full-text architecture สำหรับข้อมูลขนาดใหญ่

---

## 14. Testing Matrix

ทดสอบ:

- ภาษาไทยล้วน
- ไทย + อังกฤษ
- Emoji
- Combining marks
- Long text
- Invalid UTF-8
- Timezone boundary
- Leap day
- พ.ศ./ค.ศ.
- Currency decimal
- Missing translation key

---

## แบบฝึกปฏิบัติ

1. เปรียบเทียบ `strlen`, `mb_strlen` และ `grapheme_strlen`
2. สร้าง Thai name sorter ด้วย Collator
3. สร้าง currency/date formatter service
4. ทดสอบเวลา UTC กับ Asia/Bangkok
5. สร้าง translation catalog ไทย/อังกฤษ
6. วิเคราะห์ homoglyph risk ของ approval code

---

## Production Checklist

- [ ] Database และ connection ใช้ UTF-8/utf8mb4
- [ ] UI length ใช้ grapheme เมื่อเหมาะสม
- [ ] มี normalization policy
- [ ] วันที่เก็บ UTC และแสดงตาม timezone
- [ ] Currency ไม่เก็บเป็น formatted string
- [ ] Translation มี fallback และ review
- [ ] Identifier security แยกจาก display name
- [ ] Test ครอบคลุมภาษาไทยและ emoji
- [ ] Search architecture รองรับภาษาไทยจริง

---

## References

- PHP intl — https://www.php.net/manual/en/book.intl.php
- PHP mbstring — https://www.php.net/manual/en/book.mbstring.php
- PHP Normalizer — https://www.php.net/manual/en/class.normalizer.php
- PHP Collator — https://www.php.net/manual/en/class.collator.php
- ICU Documentation — https://unicode-org.github.io/icu/
- Unicode Standard — https://www.unicode.org/standard/standard.html
