<?php
/**
 * EspoCRM Billing Setup Script
 * Run inside the Docker container: php /tmp/billing_setup.php
 */

function writeFile(string $path, string $content): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($path, $content);
    echo "  Written: $path\n";
}

echo "=== EspoCRM Billing Setup ===\n\n";

// --- SCOPES ---
echo "[1/6] Writing scope metadata...\n";

writeFile('/var/www/html/custom/Espo/Custom/Resources/metadata/scopes/Invoice.json', json_encode([
    'entity' => true, 'object' => true, 'isCustom' => true, 'layouts' => true,
    'tab' => true, 'acl' => true, 'customizable' => true, 'pdfTemplates' => true,
    'activities' => false, 'history' => false, 'stream' => true, 'disabled' => false,
    'importable' => true, 'exportable' => true, 'type' => 'Base',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

writeFile('/var/www/html/custom/Espo/Custom/Resources/metadata/scopes/InvoiceItem.json', json_encode([
    'entity' => true, 'object' => true, 'isCustom' => true, 'layouts' => true,
    'tab' => false, 'acl' => false, 'customizable' => true, 'stream' => false,
    'disabled' => false, 'importable' => false, 'exportable' => false, 'type' => 'Base',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// --- ENTITY DEFS ---
echo "\n[2/6] Writing entityDefs...\n";

writeFile('/var/www/html/custom/Espo/Custom/Resources/metadata/entityDefs/Invoice.json', json_encode([
    'fields' => [
        'name' => ['type' => 'varchar', 'required' => true, 'pattern' => '$noBadCharacters'],
        'number' => ['type' => 'varchar', 'maxLength' => 36, 'readOnly' => true],
        'status' => [
            'type' => 'enum',
            'options' => ['Draft', 'Sent', 'Paid', 'Overdue', 'Cancelled'],
            'default' => 'Draft',
            'style' => ['Draft' => 'default', 'Sent' => 'primary', 'Paid' => 'success', 'Overdue' => 'danger', 'Cancelled' => 'default'],
        ],
        'account' => ['type' => 'link', 'entity' => 'Account'],
        'contact' => ['type' => 'link', 'entity' => 'Contact'],
        'dateInvoiced' => ['type' => 'date'],
        'dateDue' => ['type' => 'date'],
        'amount' => ['type' => 'currency', 'readOnly' => true],
        'paymentMethod' => ['type' => 'enum', 'options' => ['', 'Cash', 'Check', 'Zelle', 'CashApp', 'Card/Stripe'], 'default' => ''],
        'stripePaymentLink' => ['type' => 'url', 'maxLength' => 500],
        'notes' => ['type' => 'text'],
    ],
    'links' => [
        'account' => ['type' => 'belongsTo', 'entity' => 'Account', 'foreign' => 'invoices'],
        'contact' => ['type' => 'belongsTo', 'entity' => 'Contact', 'foreign' => 'invoices'],
        'items' => ['type' => 'hasMany', 'entity' => 'InvoiceItem', 'foreign' => 'invoice'],
    ],
    'collection' => ['sortBy' => 'createdAt', 'asc' => false],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

writeFile('/var/www/html/custom/Espo/Custom/Resources/metadata/entityDefs/InvoiceItem.json', json_encode([
    'fields' => [
        'name' => ['type' => 'varchar', 'required' => true],
        'invoice' => ['type' => 'link', 'entity' => 'Invoice', 'required' => true],
        'description' => ['type' => 'text'],
        'tier' => ['type' => 'enum', 'options' => ['', 'Tier 1 - Kickstart', 'Tier 2 - Accelerate', 'Tier 3 - Overdrive', 'Add-on', 'Custom']],
        'itemType' => ['type' => 'enum', 'options' => ['', 'Setup Fee', 'Monthly Retainer', 'Add-on', 'Other'], 'default' => ''],
        'quantity' => ['type' => 'int', 'default' => 1],
        'unitPrice' => ['type' => 'currency'],
        'amount' => ['type' => 'currency', 'readOnly' => true],
    ],
    'links' => [
        'invoice' => ['type' => 'belongsTo', 'entity' => 'Invoice', 'foreign' => 'items'],
    ],
    'collection' => ['sortBy' => 'createdAt', 'asc' => true],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// --- CLIENT DEFS ---
echo "\n[3/6] Writing clientDefs...\n";

writeFile('/var/www/html/custom/Espo/Custom/Resources/metadata/clientDefs/Invoice.json', json_encode([
    'iconClass' => 'fas fa-file-invoice-dollar',
    'views' => (object)[], 'recordViews' => (object)[], 'detailLayout' => null, 'menu' => (object)[],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

writeFile('/var/www/html/custom/Espo/Custom/Resources/metadata/clientDefs/InvoiceItem.json', json_encode([
    'iconClass' => 'fas fa-list', 'views' => (object)[], 'recordViews' => (object)[],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// --- LAYOUTS ---
echo "\n[4/6] Writing layouts...\n";

writeFile('/var/www/html/custom/Espo/Custom/Resources/layouts/Invoice/list.json', json_encode([
    ['name' => 'number', 'link' => true], ['name' => 'name'], ['name' => 'account'],
    ['name' => 'status'], ['name' => 'amount'], ['name' => 'dateInvoiced'],
    ['name' => 'dateDue'], ['name' => 'paymentMethod'],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

writeFile('/var/www/html/custom/Espo/Custom/Resources/layouts/Invoice/detail.json', json_encode([
    ['rows' => [
        [['name' => 'number'], ['name' => 'status']],
        [['name' => 'name'], false],
        [['name' => 'account'], ['name' => 'contact']],
        [['name' => 'dateInvoiced'], ['name' => 'dateDue']],
        [['name' => 'amount'], ['name' => 'paymentMethod']],
        [['name' => 'stripePaymentLink'], false],
        [['name' => 'notes'], false],
    ]],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

writeFile('/var/www/html/custom/Espo/Custom/Resources/layouts/Invoice/edit.json', json_encode([
    ['rows' => [
        [['name' => 'name'], ['name' => 'status']],
        [['name' => 'account'], ['name' => 'contact']],
        [['name' => 'dateInvoiced'], ['name' => 'dateDue']],
        [['name' => 'paymentMethod'], ['name' => 'stripePaymentLink']],
        [['name' => 'notes'], false],
    ]],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

writeFile('/var/www/html/custom/Espo/Custom/Resources/layouts/Invoice/relationships.json',
    json_encode(['items'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

writeFile('/var/www/html/custom/Espo/Custom/Resources/layouts/InvoiceItem/list.json', json_encode([
    ['name' => 'name', 'link' => true], ['name' => 'tier'], ['name' => 'itemType'],
    ['name' => 'quantity'], ['name' => 'unitPrice'], ['name' => 'amount'],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

writeFile('/var/www/html/custom/Espo/Custom/Resources/layouts/InvoiceItem/detail.json', json_encode([
    ['rows' => [
        [['name' => 'name'], ['name' => 'tier']],
        [['name' => 'itemType'], ['name' => 'quantity']],
        [['name' => 'unitPrice'], ['name' => 'amount']],
        [['name' => 'description'], false],
    ]],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

writeFile('/var/www/html/custom/Espo/Custom/Resources/layouts/InvoiceItem/edit.json', json_encode([
    ['rows' => [
        [['name' => 'name'], ['name' => 'tier']],
        [['name' => 'itemType'], ['name' => 'quantity']],
        [['name' => 'unitPrice'], false],
        [['name' => 'description'], false],
    ]],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// --- HOOKS ---
echo "\n[5/6] Writing hooks...\n";

writeFile('/var/www/html/custom/Espo/Custom/Hooks/Invoice/BeforeSave.php', <<<'HOOK'
<?php
namespace Espo\Custom\Hooks\Invoice;
use Espo\ORM\Entity;
class BeforeSave
{
    protected $entityManager;
    public function __construct(\Espo\ORM\EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    public function beforeSave(Entity $entity, array $options = []): void
    {
        if ($entity->isNew() && !$entity->get('number')) {
            $year   = date('Y');
            $prefix = 'INV-' . $year . '-';
            $pdo = $this->entityManager->getPDO();
            $sql = "SELECT MAX(CAST(SUBSTRING(number, " . (strlen($prefix) + 1) . ") AS UNSIGNED)) as maxNum
                    FROM invoice WHERE number LIKE :prefix AND deleted = 0";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':prefix' => $prefix . '%']);
            $row  = $stmt->fetch(\PDO::FETCH_ASSOC);
            $next = ($row && $row['maxNum']) ? ((int)$row['maxNum'] + 1) : 1;
            $entity->set('number', $prefix . str_pad($next, 4, '0', STR_PAD_LEFT));
        }
    }
}
HOOK);

writeFile('/var/www/html/custom/Espo/Custom/Hooks/InvoiceItem/BeforeSave.php', <<<'HOOK'
<?php
namespace Espo\Custom\Hooks\InvoiceItem;
use Espo\ORM\Entity;
class BeforeSave
{
    public function beforeSave(Entity $entity, array $options = []): void
    {
        $qty   = (float)($entity->get('quantity') ?? 1);
        $price = (float)($entity->get('unitPrice') ?? 0);
        $entity->set('amount', round($qty * $price, 2));
        $entity->set('amountCurrency', $entity->get('unitPriceCurrency') ?? 'USD');
    }
}
HOOK);

writeFile('/var/www/html/custom/Espo/Custom/Hooks/InvoiceItem/AfterSave.php', <<<'HOOK'
<?php
namespace Espo\Custom\Hooks\InvoiceItem;
use Espo\ORM\Entity;
class AfterSave
{
    protected $entityManager;
    public function __construct(\Espo\ORM\EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    public function afterSave(Entity $entity, array $options = []): void
    {
        $this->recalculateInvoice($entity);
    }
    private function recalculateInvoice(Entity $entity): void
    {
        $invoiceId = $entity->get('invoiceId');
        if (!$invoiceId) return;
        $pdo  = $this->entityManager->getPDO();
        $sql  = "SELECT SUM(amount) as total FROM invoice_item WHERE invoice_id = :id AND deleted = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $invoiceId]);
        $row   = $stmt->fetch(\PDO::FETCH_ASSOC);
        $total = $row ? (float)$row['total'] : 0;
        $invoice = $this->entityManager->getEntity('Invoice', $invoiceId);
        if ($invoice) {
            $invoice->set('amount', $total);
            $invoice->set('amountCurrency', 'USD');
            $this->entityManager->saveEntity($invoice, ['skipHooks' => true]);
        }
    }
}
HOOK);

writeFile('/var/www/html/custom/Espo/Custom/Hooks/InvoiceItem/AfterRemove.php', <<<'HOOK'
<?php
namespace Espo\Custom\Hooks\InvoiceItem;
use Espo\ORM\Entity;
class AfterRemove
{
    protected $entityManager;
    public function __construct(\Espo\ORM\EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    public function afterRemove(Entity $entity, array $options = []): void
    {
        $invoiceId = $entity->get('invoiceId');
        if (!$invoiceId) return;
        $pdo  = $this->entityManager->getPDO();
        $sql  = "SELECT SUM(amount) as total FROM invoice_item WHERE invoice_id = :id AND deleted = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $invoiceId]);
        $row   = $stmt->fetch(\PDO::FETCH_ASSOC);
        $total = $row ? (float)$row['total'] : 0;
        $invoice = $this->entityManager->getEntity('Invoice', $invoiceId);
        if ($invoice) {
            $invoice->set('amount', $total);
            $invoice->set('amountCurrency', 'USD');
            $this->entityManager->saveEntity($invoice, ['skipHooks' => true]);
        }
    }
}
HOOK);

// --- CLEAR CACHE ---
echo "\n[6/6] Clearing EspoCRM cache...\n";

foreach (['/var/www/html/data/cache', '/var/www/html/data/resources'] as $dir) {
    if (!is_dir($dir)) { echo "  Skipping (not found): $dir\n"; continue; }
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    $count = 0;
    foreach ($files as $fi) {
        $fi->isDir() ? @rmdir($fi->getRealPath()) : (@unlink($fi->getRealPath()) && $count++);
    }
    echo "  Cleared $count file(s) from: $dir\n";
}

// --- CREATE DATABASE TABLES ---
echo "\n[7/7] Creating database tables...\n";

// Load EspoCRM config to get DB credentials
$configPath = '/var/www/html/data/config.php';
if (!file_exists($configPath)) {
    echo "  ERROR: Cannot find EspoCRM config at $configPath\n";
    exit(1);
}
$config = include $configPath;

$host   = $config['database']['host'] ?? 'localhost';
$port   = $config['database']['port'] ?? '3306';
$dbname = $config['database']['dbname'] ?? '';
$user   = $config['database']['user'] ?? '';
$pass   = $config['database']['password'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create invoice table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `invoice` (
        `id` VARCHAR(24) NOT NULL,
        `name` VARCHAR(255) DEFAULT NULL,
        `number` VARCHAR(36) DEFAULT NULL,
        `status` VARCHAR(255) DEFAULT 'Draft',
        `account_id` VARCHAR(24) DEFAULT NULL,
        `account_name` VARCHAR(255) DEFAULT NULL,
        `contact_id` VARCHAR(24) DEFAULT NULL,
        `contact_name` VARCHAR(255) DEFAULT NULL,
        `date_invoiced` DATE DEFAULT NULL,
        `date_due` DATE DEFAULT NULL,
        `amount` DECIMAL(12,2) DEFAULT NULL,
        `amount_currency` VARCHAR(3) DEFAULT 'USD',
        `payment_method` VARCHAR(255) DEFAULT NULL,
        `stripe_payment_link` VARCHAR(500) DEFAULT NULL,
        `notes` MEDIUMTEXT DEFAULT NULL,
        `assigned_user_id` VARCHAR(24) DEFAULT NULL,
        `assigned_user_name` VARCHAR(255) DEFAULT NULL,
        `created_at` DATETIME DEFAULT NULL,
        `modified_at` DATETIME DEFAULT NULL,
        `created_by_id` VARCHAR(24) DEFAULT NULL,
        `modified_by_id` VARCHAR(24) DEFAULT NULL,
        `deleted` TINYINT(1) DEFAULT 0,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "  Created table: invoice\n";

    // Create invoice_item table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `invoice_item` (
        `id` VARCHAR(24) NOT NULL,
        `name` VARCHAR(255) DEFAULT NULL,
        `invoice_id` VARCHAR(24) DEFAULT NULL,
        `invoice_name` VARCHAR(255) DEFAULT NULL,
        `description` MEDIUMTEXT DEFAULT NULL,
        `tier` VARCHAR(255) DEFAULT NULL,
        `item_type` VARCHAR(255) DEFAULT NULL,
        `quantity` INT DEFAULT 1,
        `unit_price` DECIMAL(12,2) DEFAULT NULL,
        `unit_price_currency` VARCHAR(3) DEFAULT 'USD',
        `amount` DECIMAL(12,2) DEFAULT NULL,
        `amount_currency` VARCHAR(3) DEFAULT 'USD',
        `created_at` DATETIME DEFAULT NULL,
        `modified_at` DATETIME DEFAULT NULL,
        `created_by_id` VARCHAR(24) DEFAULT NULL,
        `modified_by_id` VARCHAR(24) DEFAULT NULL,
        `deleted` TINYINT(1) DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `invoice_id` (`invoice_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "  Created table: invoice_item\n";

    echo "  Database tables ready!\n";
} catch (Exception $e) {
    echo "  DB ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== Billing setup complete! ===\n";
