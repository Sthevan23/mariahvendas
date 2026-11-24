<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Helpers;
use App\Services\MediaStore;

class SettingsController extends Controller
{
    public function index(): void
    {
        $pdo = Database::connection();
        $settings = [];
        foreach ($pdo->query('SELECT `key`, value FROM settings') as $row) {
            $settings[$row['key']] = $row['value'];
        }

        $this->render('admin/settings/index', [
            'title' => 'Configurações',
            'settings' => $settings,
            'categories' => $pdo->query('SELECT * FROM categories ORDER BY sort_order')->fetchAll(),
            'coupons' => $pdo->query('SELECT * FROM coupons ORDER BY id DESC')->fetchAll(),
            'shipping' => $pdo->query('SELECT * FROM shipping_methods ORDER BY id')->fetchAll(),
            'payments' => $pdo->query('SELECT * FROM payment_methods ORDER BY id')->fetchAll(),
        ], 'layouts/admin');
    }

    public function save(): void
    {
        $this->verifyCsrf();
        $keys = [
            'store_name', 'tagline', 'whatsapp', 'instagram', 'email', 'phone', 'address',
            'hero_title', 'hero_subtitle', 'hero_image', 'og_image', 'google_site_verification', 'pinterest_verification',
            'exchange_policy', 'privacy_policy', 'delivery_info',
        ];
        foreach ($keys as $key) {
            if ($this->input($key) !== null) {
                Helpers::saveSetting($key, trim((string) $this->input($key)));
            }
        }
        $this->flash('success', 'Configurações salvas.');
        $this->redirect('/admin/configuracoes');
    }

    public function saveCategory(): void
    {
        $this->verifyCsrf();
        $id = (int) $this->input('id');
        $data = [
            'name' => trim((string) $this->input('name')),
            'image' => trim((string) $this->input('image')),
            'description' => trim((string) $this->input('description')),
            'is_active' => $this->input('is_active') ? 1 : 0,
            'sort_order' => (int) $this->input('sort_order', 0),
        ];
        if ($id) {
            \App\Models\Category::update($id, $data);
        } else {
            \App\Models\Category::create($data);
        }
        $this->flash('success', 'Categoria salva.');
        $this->redirect('/admin/configuracoes');
    }

    public function saveCoupon(): void
    {
        $this->verifyCsrf();
        $code = strtoupper(trim((string) $this->input('code')));
        $type = $this->input('type') === 'fixed' ? 'fixed' : 'percent';
        $value = (float) str_replace(',', '.', (string) $this->input('value'));

        if ($code === '') {
            $this->flash('error', 'Informe o código do cupom.');
            $this->redirect('/admin/configuracoes');
        }
        if ($value <= 0 || ($type === 'percent' && $value > 100)) {
            $this->flash('error', 'Valor inválido: percentual deve ficar entre 0 e 100.');
            $this->redirect('/admin/configuracoes');
        }

        try {
            Database::connection()->prepare(
                'INSERT INTO coupons (code, type, value, min_subtotal, is_active, expires_at) VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([
                $code,
                $type,
                $value,
                (float) $this->input('min_subtotal', 0),
                $this->input('is_active') ? 1 : 0,
                $this->input('expires_at') ?: null,
            ]);
        } catch (\PDOException $e) {
            $this->flash('error', 'Já existe um cupom com este código.');
            $this->redirect('/admin/configuracoes');
        }

        $this->flash('success', 'Cupom criado.');
        $this->redirect('/admin/configuracoes');
    }

    public function toggleCoupon(string $id): void
    {
        $this->verifyCsrf('/admin/configuracoes');
        Database::connection()->prepare('UPDATE coupons SET is_active = 1 - is_active WHERE id = ?')->execute([(int) $id]);
        $this->flash('success', 'Status do cupom alterado.');
        $this->redirect('/admin/configuracoes');
    }

    public function deleteCoupon(string $id): void
    {
        $this->verifyCsrf('/admin/configuracoes');
        Database::connection()->prepare('DELETE FROM coupons WHERE id = ?')->execute([(int) $id]);
        $this->flash('success', 'Cupom excluído.');
        $this->redirect('/admin/configuracoes');
    }

    public function uploadHero(): void
    {
        $this->verifyCsrf();
        $err = $_FILES['hero_file']['error'] ?? UPLOAD_ERR_NO_FILE;
        if (empty($_FILES['hero_file']['name']) || $err !== UPLOAD_ERR_OK) {
            $msg = in_array($err, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                ? 'A foto e muito grande para o servidor. Diminua a imagem (ate uns 5 MB) e tente de novo.'
                : 'Selecione uma imagem JPG, PNG ou WebP.';
            $this->flash('error', $msg);
            $this->redirect('/admin/configuracoes');
        }

        $res = MediaStore::saveUpload($_FILES['hero_file'], 2400);
        if (!$res['ok']) {
            $this->flash('error', 'Nao foi possivel salvar a capa: ' . ($res['error'] ?? 'erro'));
            $this->redirect('/admin/configuracoes');
        }
        Helpers::saveSetting('hero_image', $res['path']);

        $this->flash('success', 'Capa do site atualizada.');
        $this->redirect('/admin/configuracoes');
    }

    /** Cards da seção "Coleções" da home (texto, foto e link editáveis no painel). */
    public static function collectionTiles(): array
    {
        $defaults = [
            1 => ['small' => 'Festa & formatura', 'title' => 'Coleção Festa', 'image' => '/uploads/moda/colecao/mariah-02.jpg', 'link' => '/categoria/colecao-atelier'],
            2 => ['small' => 'Silhueta', 'title' => 'Vestidos', 'image' => '/uploads/moda/colecao/mariah-04.jpg', 'link' => '/categoria/vestidos'],
            3 => ['small' => 'Recém-chegados', 'title' => 'Novidades', 'image' => '/uploads/moda/colecao/mariah-06.jpg', 'link' => '/categoria/novidades'],
        ];
        $tiles = [];
        foreach ($defaults as $n => $d) {
            $row = [];
            foreach ($d as $k => $v) {
                $val = Helpers::setting("col{$n}_{$k}");
                $row[$k] = is_string($val) && trim($val) !== '' ? $val : $v;
                if ($k === 'image') {
                    $row[$k] = Helpers::imageOrDefault($row[$k], $v);
                }
            }
            $row['n'] = $n;
            $row['hidden'] = Helpers::setting("col{$n}_hidden") === '1';
            if (!$row['hidden']) {
                $tiles[] = $row;
            }
        }
        return $tiles;
    }

    public function saveCollections(): void
    {
        $this->verifyCsrf('/admin/configuracoes#colecoes');
        $dir = dirname(__DIR__, 3) . '/public/uploads/moda/colecao/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $problems = [];
        for ($n = 1; $n <= 3; $n++) {
            Helpers::saveSetting("col{$n}_title", trim((string) $this->input("col{$n}_title")));
            Helpers::saveSetting("col{$n}_small", trim((string) $this->input("col{$n}_small")));
            $link = trim((string) $this->input("col{$n}_link"));
            if ($link !== '' && !preg_match('#^(/|https?://)#', $link)) {
                $link = '/' . $link;
            }
            Helpers::saveSetting("col{$n}_link", $link);
            Helpers::saveSetting("col{$n}_hidden", $this->input("col{$n}_hidden") ? '1' : '0');

            $f = $_FILES["col{$n}_file"] ?? null;
            if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($f['error'] !== UPLOAD_ERR_OK) {
                $problems[] = "foto do card $n grande demais";
                continue;
            }
            $res = MediaStore::saveUpload($f, 1400);
            if (!$res['ok']) {
                $problems[] = "foto do card $n: " . ($res['error'] ?? 'erro');
                continue;
            }
            Helpers::saveSetting("col{$n}_image", $res['path']);
        }
        $this->flash($problems ? 'error' : 'success', $problems
            ? 'Salvei os textos, mas: ' . implode('; ', $problems) . '. Use fotos de ate uns 5 MB.'
            : 'Coleções da home atualizadas.');
        $this->redirect('/admin/configuracoes#colecoes');
    }

    /** Galeria de fotos da home (seção Instagram): excluir uma, excluir todas ou adicionar. */
    public function saveGallery(): void
    {
        $this->verifyCsrf('/admin/configuracoes#galeria');
        $feed = json_decode((string) Helpers::setting('instagram_feed', '[]'), true) ?: [];
        $feed = array_values(array_filter($feed, 'is_string'));
        $dir = dirname(__DIR__, 3) . '/public/uploads/moda/galeria/';
        $drop = function (string $path) use ($dir): void {
            // So apaga arquivo enviado pelo painel; as fotos antigas do tema ficam no disco.
            if (preg_match('#^/uploads/moda/galeria/[A-Za-z0-9._-]+$#', $path)) {
                @unlink($dir . basename($path));
            }
        };
        $action = (string) $this->input('action');
        $msg = 'Galeria atualizada.';

        if ($action === 'remove') {
            $path = (string) $this->input('path');
            $feed = array_values(array_filter($feed, function ($p) use ($path, $drop) {
                if ($p === $path) {
                    $drop($p);
                    return false;
                }
                return true;
            }));
            $msg = 'Foto excluída da home.';
        } elseif ($action === 'clear') {
            foreach ($feed as $p) {
                $drop($p);
            }
            $feed = [];
            $msg = 'Todas as fotos foram excluídas. A seção saiu da home.';
        } elseif ($action === 'add') {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $files = $_FILES['gallery_files'] ?? null;
            $added = 0;
            $bad = 0;
            if ($files && is_array($files['name'])) {
                foreach ($files['name'] as $i => $name) {
                    if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    $one = [
                        'name' => $name,
                        'type' => $files['type'][$i] ?? '',
                        'tmp_name' => $files['tmp_name'][$i] ?? '',
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i] ?? 0,
                    ];
                    $res = MediaStore::saveUpload($one, 1200);
                    if (!$res['ok']) {
                        $bad++;
                        continue;
                    }
                    $feed[] = $res['path'];
                    $added++;
                }
            }
            $msg = $added ? "$added foto(s) adicionada(s)." : 'Nenhuma foto foi adicionada.';
            if ($bad) {
                $msg .= " $bad arquivo(s) não puderam ser lidos (use JPG, PNG ou WebP de até uns 5 MB).";
            }
        }

        Helpers::saveSetting('instagram_feed', json_encode($feed, JSON_UNESCAPED_SLASHES));
        $this->flash('success', $msg);
        $this->redirect('/admin/configuracoes#galeria');
    }

    private function convertHeroToJpeg(string $source, string $jpgPath, string $ext): void
    {
        if ($ext === 'webp' && function_exists('imagecreatefromwebp')) {
            $img = @imagecreatefromwebp($source);
        } elseif ($ext === 'png') {
            $img = @imagecreatefrompng($source);
        } else {
            return;
        }
        if (!$img) {
            return;
        }
        imagejpeg($img, $jpgPath, 92);
        imagedestroy($img);
    }
}
