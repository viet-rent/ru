<?php
/**
 * Единый файл для парсинга страниц listings.php
 *
 * - Если запрос POST с ids[] → AJAX-обработчик, возвращает JSON
 * - Иначе → отображается веб-интерфейс
 */

// ===== КОНФИГУРАЦИЯ =====
$saveDir = __DIR__ . '/id';
$baseUrl = 'http://bczdslgma5.temp.swtest.ru/listings.php?id=';
$timeout = 10;

// ===== ОБРАБОТЧИК AJAX =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids'])) {
    // Создаём папку, если нет
    if (!is_dir($saveDir)) {
        mkdir($saveDir, 0777, true);
    }

    $ids = (array)$_POST['ids'];
    $ids = array_filter($ids, 'is_numeric');
    $ids = array_unique($ids);

    $result = ['success' => [], 'errors' => []];

    foreach ($ids as $id) {
        $id = (int)$id;
        $filePath = $saveDir . '/' . $id . '.html';
        $url = $baseUrl . $id;
        $html = fetchPage($url, $timeout);

        if ($html !== false) {
            if (file_put_contents($filePath, $html) !== false) {
                $result['success'][] = $id;
            } else {
                $result['errors'][$id] = 'Ошибка записи файла';
            }
        } else {
            $result['errors'][$id] = 'Не удалось загрузить страницу';
        }
    }

    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// ===== ФУНКЦИЯ ЗАГРУЗКИ СТРАНИЦЫ =====
function fetchPage($url, $timeout) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HEADER => false,
        CURLOPT_MAXREDIRS => 3,
    ]);
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || $httpCode !== 200) {
        return false;
    }
    return $html;
}

// ===== ВЕБ-ИНТЕРФЕЙС =====
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Парсер страниц listings.php</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Roboto, sans-serif; background: #f4f6f9; padding: 40px 20px; display: flex; justify-content: center; }
        .container { max-width: 900px; width: 100%; background: white; border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        h1 { font-size: 26px; margin-bottom: 8px; color: #1a1a2e; }
        .sub { color: #6c757d; margin-bottom: 25px; font-size: 14px; }
        textarea { width: 100%; height: 200px; padding: 15px; font-size: 14px; border: 2px solid #e2e8f0; border-radius: 12px; resize: vertical; font-family: monospace; transition: border 0.2s; }
        textarea:focus { border-color: #4f46e5; outline: none; box-shadow: 0 0 0 3px rgba(79,70,229,0.1); }
        .actions { display: flex; gap: 15px; flex-wrap: wrap; margin: 20px 0 25px; align-items: center; }
        .btn { padding: 12px 30px; font-size: 16px; font-weight: 600; border: none; border-radius: 40px; cursor: pointer; transition: all 0.2s; }
        .btn-primary { background: #4f46e5; color: white; }
        .btn-primary:hover { background: #4338ca; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(79,70,229,0.3); }
        .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }
        .btn-secondary { background: #e2e8f0; color: #1a1a2e; }
        .btn-secondary:hover { background: #cbd5e1; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .progress-wrap { background: #e2e8f0; border-radius: 40px; height: 12px; overflow: hidden; margin: 15px 0; }
        .progress-bar { height: 100%; width: 0%; background: linear-gradient(90deg, #4f46e5, #7c3aed); border-radius: 40px; transition: width 0.3s ease; }
        .status { display: flex; justify-content: space-between; font-size: 14px; color: #4a5568; margin-top: 6px; }
        .log { background: #1e293b; color: #a5f3fc; padding: 15px; border-radius: 12px; max-height: 300px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 13px; margin-top: 20px; white-space: pre-wrap; word-break: break-all; }
        .log .success { color: #4ade80; }
        .log .error { color: #f87171; }
        .log .info { color: #60a5fa; }
        .stats { display: flex; gap: 30px; flex-wrap: wrap; margin: 15px 0; font-size: 15px; }
        .stats span { background: #f1f5f9; padding: 6px 16px; border-radius: 30px; display: inline-block; }
        .stats strong { color: #1a1a2e; }
        .batch-size { display: flex; align-items: center; gap: 10px; }
        .batch-size label { font-weight: 500; }
        .batch-size input { width: 70px; padding: 6px 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; text-align: center; }
        .hint { color: #6c757d; font-size: 13px; margin-top: 5px; }
    </style>
</head>
<body>
<div class="container">
    <h1>📥 Парсер страниц listings.php</h1>
    <p class="sub">Вставьте ссылки или ID (каждый с новой строки). Скрипт сохранит HTML-код в папку <strong>id/</strong></p>

    <form id="parserForm">
        <textarea id="linksInput" placeholder="http://bczdslgma5.temp.swtest.ru/listings.php?id=1983&#10;1984&#10;1985&#10;..."></textarea>
        <div class="hint">Поддерживаются полные URL или просто ID (числа).</div>

        <div class="actions">
            <button type="submit" class="btn btn-primary" id="startBtn">🚀 СПАРСИТЬ</button>
            <button type="button" class="btn btn-secondary" id="clearBtn">Очистить</button>
            <div class="batch-size">
                <label for="batchSize">Порция:</label>
                <input type="number" id="batchSize" value="20" min="5" max="100">
            </div>
        </div>
    </form>

    <div class="stats">
        <span>Всего: <strong id="totalCount">0</strong></span>
        <span>Успешно: <strong id="successCount">0</strong></span>
        <span>Ошибок: <strong id="errorCount">0</strong></span>
        <span>Обработано: <strong id="processedCount">0</strong></span>
    </div>

    <div class="progress-wrap">
        <div class="progress-bar" id="progressBar"></div>
    </div>
    <div class="status">
        <span id="statusText">Готов к работе</span>
        <span id="percentText">0%</span>
    </div>

    <div class="log" id="logContainer">
        <div class="info">⏳ Ожидаем начала...</div>
    </div>
</div>

<script>
    (function() {
        const linksInput = document.getElementById('linksInput');
        const startBtn = document.getElementById('startBtn');
        const clearBtn = document.getElementById('clearBtn');
        const batchSizeInput = document.getElementById('batchSize');
        const progressBar = document.getElementById('progressBar');
        const statusText = document.getElementById('statusText');
        const percentText = document.getElementById('percentText');
        const logContainer = document.getElementById('logContainer');
        const totalCount = document.getElementById('totalCount');
        const successCount = document.getElementById('successCount');
        const errorCount = document.getElementById('errorCount');
        const processedCount = document.getElementById('processedCount');

        let isRunning = false;
        let currentBatch = 0;
        let totalBatches = 0;
        let processed = 0;
        let success = 0;
        let errors = 0;
        let idList = [];

        function addLog(message, type = 'info') {
            const entry = document.createElement('div');
            entry.className = type;
            entry.textContent = message;
            logContainer.appendChild(entry);
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        function updateStats() {
            totalCount.textContent = idList.length;
            successCount.textContent = success;
            errorCount.textContent = errors;
            processedCount.textContent = processed;
        }

        function updateProgress() {
            const total = idList.length;
            const done = processed;
            const percent = total > 0 ? Math.round((done / total) * 100) : 0;
            progressBar.style.width = percent + '%';
            percentText.textContent = percent + '%';
            statusText.textContent = isRunning ? `Обработка... ${done} из ${total}` : (done === total ? '✅ Завершено!' : 'Готов к работе');
        }

        function resetUI() {
            isRunning = false;
            startBtn.disabled = false;
            startBtn.textContent = '🚀 СПАРСИТЬ';
            progressBar.style.width = '0%';
            percentText.textContent = '0%';
            statusText.textContent = 'Готов к работе';
            logContainer.innerHTML = '<div class="info">⏳ Ожидаем начала...</div>';
            processed = 0;
            success = 0;
            errors = 0;
            idList = [];
            updateStats();
            updateProgress();
        }

        // Извлечение ID из строки (ссылка или число)
        function extractId(str) {
            str = str.trim();
            if (!str) return null;
            if (/^\d+$/.test(str)) return parseInt(str, 10);
            const match = str.match(/[?&]id=(\d+)/i);
            if (match) return parseInt(match[1], 10);
            const numMatch = str.match(/\b(\d{4,})\b/);
            if (numMatch) return parseInt(numMatch[1], 10);
            return null;
        }

        function prepareIds() {
            const raw = linksInput.value.split('\n');
            const ids = [];
            raw.forEach(line => {
                const id = extractId(line);
                if (id !== null && !isNaN(id) && id > 0) {
                    ids.push(id);
                }
            });
            return [...new Set(ids)];
        }

        // Отправить одну порцию
        function sendBatch(batch) {
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'parser.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            resolve(response);
                        } catch (e) {
                            reject('Ошибка парсинга ответа сервера');
                        }
                    } else {
                        reject('HTTP ' + xhr.status);
                    }
                };
                xhr.onerror = function() {
                    reject('Сетевая ошибка');
                };
                const data = 'ids[]=' + batch.join('&ids[]=');
                xhr.send(data);
            });
        }

        // Основной процесс
        async function startParsing() {
            if (isRunning) return;

            idList = prepareIds();
            if (idList.length === 0) {
                alert('Не найдено ни одного ID. Введите числа или ссылки с id=...');
                return;
            }

            processed = 0;
            success = 0;
            errors = 0;
            isRunning = true;
            startBtn.disabled = true;
            startBtn.textContent = '⏳ Парсинг...';
            logContainer.innerHTML = '';
            addLog(`📋 Найдено ${idList.length} уникальных ID`, 'info');

            const batchSize = parseInt(batchSizeInput.value, 10) || 20;
            const batches = [];
            for (let i = 0; i < idList.length; i += batchSize) {
                batches.push(idList.slice(i, i + batchSize));
            }
            totalBatches = batches.length;
            currentBatch = 0;
            updateStats();
            updateProgress();

            for (let batch of batches) {
                if (!isRunning) break;
                currentBatch++;
                addLog(`🔄 Обработка порции ${currentBatch}/${totalBatches} (ID: ${batch.join(', ')})`, 'info');
                try {
                    const result = await sendBatch(batch);
                    const batchSuccess = result.success || [];
                    const batchErrors = result.errors || {};
                    success += batchSuccess.length;
                    errors += Object.keys(batchErrors).length;
                    processed += batch.length;

                    batchSuccess.forEach(id => {
                        addLog(`✅ ${id} сохранён`, 'success');
                    });
                    for (const [id, reason] of Object.entries(batchErrors)) {
                        addLog(`❌ ${id} — ${reason}`, 'error');
                    }

                    updateStats();
                    updateProgress();
                } catch (err) {
                    addLog(`⚠️ Ошибка при отправке порции ${currentBatch}: ${err}`, 'error');
                    errors += batch.length;
                    processed += batch.length;
                    updateStats();
                    updateProgress();
                }
                await new Promise(r => setTimeout(r, 300));
            }

            isRunning = false;
            startBtn.disabled = false;
            startBtn.textContent = '🚀 СПАРСИТЬ';
            statusText.textContent = '✅ Завершено!';
            addLog('🎉 Парсинг завершён', 'info');
            updateProgress();
        }

        // Обработчик формы
        document.getElementById('parserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            startParsing();
        });

        // Очистка
        clearBtn.addEventListener('click', function() {
            if (isRunning) {
                if (!confirm('Парсинг выполняется. Остановить и очистить?')) return;
                isRunning = false;
                startBtn.disabled = false;
                startBtn.textContent = '🚀 СПАРСИТЬ';
                statusText.textContent = '⏹ Остановлен';
                addLog('⏹ Парсинг остановлен пользователем', 'error');
            } else {
                resetUI();
                linksInput.value = '';
            }
        });

        // Инициализация
        resetUI();

        // Сохранение в localStorage
        const saved = localStorage.getItem('parserInput');
        if (saved) linksInput.value = saved;
        linksInput.addEventListener('input', function() {
            localStorage.setItem('parserInput', this.value);
        });
    })();
</script>
</body>
</html>