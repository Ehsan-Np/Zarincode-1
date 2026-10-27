<?php
use PHPUnit\Framework\TestCase;
use Zarincode\Theme\Elementor\Kit_Defaults;

require_once dirname(__DIR__, 2) . '/zarincode/inc/Elementor/Kit_Defaults.php';

/** Factory-only kit palette migration rules; no Elementor runtime required. */
final class KitDefaultsTest extends TestCase {
    public function testFactoryColorsAreReplacedWithAccessibleValues(): void {
        $rows = [
            ['_id' => 'primary', 'title' => 'Primary', 'color' => '#6EC1E4'],
            ['_id' => 'secondary', 'title' => 'Secondary', 'color' => '#54595F'],
            ['_id' => 'text', 'title' => 'Text', 'color' => '#7A7A7A'],
            ['_id' => 'accent', 'title' => 'Accent', 'color' => '#61CE70'],
        ];
        list( $colors, $changed ) = Kit_Defaults::migrate_colors( $rows );
        self::assertTrue( $changed );
        self::assertSame( '#1F6B8F', $colors[0]['color'] );
        self::assertSame( '#54595F', $colors[1]['color'], 'Non-failing factory colour must stay untouched.' );
        self::assertSame( '#5F6368', $colors[2]['color'] );
        self::assertSame( '#202227', $colors[3]['color'] );
        self::assertSame( 'Primary', $colors[0]['title'], 'Non-colour metadata must survive.' );
    }
    public function testAuthorCustomizationsAndMalformedRowsAreNeverTouched(): void {
        $rows = [
            ['_id' => 'accent', 'title' => 'Accent', 'color' => '#FDD05B'],
            ['_id' => 'text', 'color' => '#7a7a7a'],
            ['_id' => 'accent', 'color' => null],
            ['color' => '#61CE70'],
            'broken',
            ['_id' => 'unknown', 'color' => '#61CE70'],
        ];
        list( $colors, $changed ) = Kit_Defaults::migrate_colors( $rows );
        self::assertTrue( $changed, 'Case-insensitive factory match must still migrate exactly the factory row.' );
        self::assertSame( '#FDD05B', $colors[0]['color'], 'Author colour must be preserved.' );
        self::assertSame( '#5F6368', $colors[1]['color'], 'Lowercase factory value is the factory default too.' );
        self::assertNull( $colors[2]['color'], 'Malformed rows must be left alone, never repaired silently.' );
        self::assertSame( 'broken', $colors[4] );
        self::assertSame( '#61CE70', $colors[5]['color'] );
    }
    public function testMigrationIsIdempotent(): void {
        $rows = [['_id' => 'accent', 'color' => '#61CE70']];
        list( $once, $changed ) = Kit_Defaults::migrate_colors( $rows );
        self::assertTrue( $changed );
        list( $twice, $changed_again ) = Kit_Defaults::migrate_colors( $once );
        self::assertFalse( $changed_again );
        self::assertSame( $once, $twice );
    }
}
