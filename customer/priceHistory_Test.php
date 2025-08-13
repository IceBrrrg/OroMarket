<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Price History API Tester</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 2.5em;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .api-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border: 1px solid #e1e8ed;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .api-section:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .section-title {
            font-size: 1.4em;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .form-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .input-group {
            display: flex;
            flex-direction: column;
        }
        
        label {
            font-weight: 500;
            color: #555;
            margin-bottom: 5px;
            font-size: 0.9em;
        }
        
        input, select, textarea {
            padding: 12px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #f8f9fa;
            color: #666;
            border: 2px solid #e1e8ed;
        }
        
        .btn-secondary:hover {
            background: #e9ecef;
            border-color: #ccc;
        }
        
        .response-container {
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .response-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .response-content {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Monaco', 'Consolas', monospace;
            white-space: pre-wrap;
            overflow-x: auto;
            font-size: 0.9em;
            line-height: 1.5;
        }
        
        .url-display {
            background: #e8f4fd;
            padding: 10px;
            border-radius: 6px;
            font-family: monospace;
            word-break: break-all;
            margin-bottom: 10px;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        
        .status-success {
            color: #28a745;
            font-weight: 600;
        }
        
        .status-error {
            color: #dc3545;
            font-weight: 600;
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        
        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
            
            .form-group {
                grid-template-columns: 1fr;
            }
            
            .container {
                margin: 10px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Price History API Tester</h1>
        
        <!-- Base URL Configuration -->
        <div class="api-section">
            <div class="section-title">⚙️ API Configuration</div>
            <div class="input-group">
                <label for="baseUrl">API Base URL:</label>
                <input type="text" id="baseUrl" value="/api/price_history_api.php" placeholder="e.g., /api/price_history_api.php">
            </div>
        </div>
        
        <div class="grid-2">
            <!-- Price History -->
            <div class="api-section">
                <div class="section-title">📈 Price History</div>
                <div class="form-group">
                    <div class="input-group">
                        <label for="historyProductId">Product ID:</label>
                        <input type="number" id="historyProductId" value="1">
                    </div>
                    <div class="input-group">
                        <label for="historyDays">Days:</label>
                        <input type="number" id="historyDays" value="30">
                    </div>
                </div>
                <button class="btn btn-primary" onclick="testPriceHistory()">Get Price History</button>
                <div id="historyResponse" class="response-container" style="display: none;">
                    <div class="response-title">Response:</div>
                    <div class="url-display" id="historyUrl"></div>
                    <div class="response-content" id="historyData"></div>
                </div>
            </div>
            
            <!-- Price Statistics -->
            <div class="api-section">
                <div class="section-title">📊 Price Statistics</div>
                <div class="form-group">
                    <div class="input-group">
                        <label for="statsProductId">Product ID:</label>
                        <input type="number" id="statsProductId" value="1">
                    </div>
                    <div class="input-group">
                        <label for="statsPeriod">Period (days):</label>
                        <input type="number" id="statsPeriod" value="30">
                    </div>
                </div>
                <button class="btn btn-primary" onclick="testPriceStatistics()">Get Statistics</button>
                <div id="statsResponse" class="response-container" style="display: none;">
                    <div class="response-title">Response:</div>
                    <div class="url-display" id="statsUrl"></div>
                    <div class="response-content" id="statsData"></div>
                </div>
            </div>
        </div>
        
        <div class="grid-2">
            <!-- Price Trends -->
            <div class="api-section">
                <div class="section-title">📈 Price Trends</div>
                <div class="form-group">
                    <div class="input-group">
                        <label for="trendsDays">Days:</label>
                        <input type="number" id="trendsDays" value="7">
                    </div>
                </div>
                <button class="btn btn-primary" onclick="testPriceTrends()">Get Price Trends</button>
                <div id="trendsResponse" class="response-container" style="display: none;">
                    <div class="response-title">Response:</div>
                    <div class="url-display" id="trendsUrl"></div>
                    <div class="response-content" id="trendsData"></div>
                </div>
            </div>
            
            <!-- Chart Data -->
            <div class="api-section">
                <div class="section-title">📊 Chart Data</div>
                <div class="form-group">
                    <div class="input-group">
                        <label for="chartProductId">Product ID:</label>
                        <input type="number" id="chartProductId" value="1">
                    </div>
                    <div class="input-group">
                        <label for="chartDays">Days:</label>
                        <input type="number" id="chartDays" value="30">
                    </div>
                </div>
                <button class="btn btn-primary" onclick="testChartData()">Get Chart Data</button>
                <div id="chartResponse" class="response-container" style="display: none;">
                    <div class="response-title">Response:</div>
                    <div class="url-display" id="chartUrl"></div>
                    <div class="response-content" id="chartData"></div>
                </div>
            </div>
        </div>
        
        <!-- Price Alert Creation -->
        <div class="api-section">
            <div class="section-title">🔔 Create Price Alert</div>
            <div class="form-group">
                <div class="input-group">
                    <label for="alertProductId">Product ID:</label>
                    <input type="number" id="alertProductId" value="1">
                </div>
                <div class="input-group">
                    <label for="alertEmail">Email:</label>
                    <input type="email" id="alertEmail" value="test@example.com">
                </div>
                <div class="input-group">
                    <label for="alertName">Customer Name:</label>
                    <input type="text" id="alertName" value="Test User">
                </div>
                <div class="input-group">
                    <label for="alertType">Alert Type:</label>
                    <select id="alertType" onchange="toggleAlertFields()">
                        <option value="price_drop">Price Drop</option>
                        <option value="price_target">Target Price</option>
                        <option value="percentage_change">Percentage Change</option>
                    </select>
                </div>
                <div class="input-group" id="targetPriceGroup">
                    <label for="targetPrice">Target Price:</label>
                    <input type="number" step="0.01" id="targetPrice" value="99.99">
                </div>
                <div class="input-group" id="thresholdGroup" style="display: none;">
                    <label for="threshold">Threshold (%):</label>
                    <input type="number" id="threshold" value="10">
                </div>
            </div>
            <button class="btn btn-primary" onclick="testCreateAlert()">Create Price Alert</button>
            <div id="alertResponse" class="response-container" style="display: none;">
                <div class="response-title">Response:</div>
                <div class="url-display" id="alertUrl"></div>
                <div class="response-content" id="alertData"></div>
            </div>
        </div>
        
        <!-- Test All Endpoints -->
        <div class="api-section">
            <div class="section-title">🚀 Quick Test All</div>
            <p style="margin-bottom: 20px; color: #666;">Test all endpoints with sample data to verify your API is working correctly.</p>
            <button class="btn btn-primary" onclick="testAllEndpoints()">Test All Endpoints</button>
            <button class="btn btn-secondary" onclick="clearAllResponses()">Clear All Responses</button>
        </div>
    </div>

    <script>
        function getBaseUrl() {
            return document.getElementById('baseUrl').value;
        }
        
        function displayResponse(elementId, urlElementId, url, data) {
            const responseContainer = document.getElementById(elementId);
            const urlDisplay = document.getElementById(urlElementId);
            const dataDisplay = document.getElementById(elementId.replace('Response', 'Data'));
            
            responseContainer.style.display = 'block';
            urlDisplay.textContent = url;
            
            try {
                dataDisplay.textContent = JSON.stringify(data, null, 2);
            } catch (e) {
                dataDisplay.textContent = data;
            }
        }
        
        async function makeRequest(url, options = {}) {
            try {
                const response = await fetch(url, options);
                const data = await response.json();
                return { success: true, data, status: response.status };
            } catch (error) {
                return { success: false, error: error.message };
            }
        }
        
        async function testPriceHistory() {
            const baseUrl = getBaseUrl();
            const productId = document.getElementById('historyProductId').value;
            const days = document.getElementById('historyDays').value;
            const url = `${baseUrl}?action=history&product_id=${productId}&days=${days}`;
            
            const result = await makeRequest(url);
            displayResponse('historyResponse', 'historyUrl', url, result);
        }
        
        async function testPriceStatistics() {
            const baseUrl = getBaseUrl();
            const productId = document.getElementById('statsProductId').value;
            const period = document.getElementById('statsPeriod').value;
            const url = `${baseUrl}?action=statistics&product_id=${productId}&period=${period}`;
            
            const result = await makeRequest(url);
            displayResponse('statsResponse', 'statsUrl', url, result);
        }
        
        async function testPriceTrends() {
            const baseUrl = getBaseUrl();
            const days = document.getElementById('trendsDays').value;
            const url = `${baseUrl}?action=trends&days=${days}`;
            
            const result = await makeRequest(url);
            displayResponse('trendsResponse', 'trendsUrl', url, result);
        }
        
        async function testChartData() {
            const baseUrl = getBaseUrl();
            const productId = document.getElementById('chartProductId').value;
            const days = document.getElementById('chartDays').value;
            const url = `${baseUrl}?action=chart_data&product_id=${productId}&days=${days}`;
            
            const result = await makeRequest(url);
            displayResponse('chartResponse', 'chartUrl', url, result);
        }
        
        async function testCreateAlert() {
            const baseUrl = getBaseUrl();
            const url = `${baseUrl}?action=create_alert`;
            
            const alertData = {
                product_id: parseInt(document.getElementById('alertProductId').value),
                email: document.getElementById('alertEmail').value,
                name: document.getElementById('alertName').value,
                alert_type: document.getElementById('alertType').value,
                target_price: document.getElementById('targetPrice').value || null,
                threshold: document.getElementById('threshold').value || null
            };
            
            const options = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(alertData)
            };
            
            const result = await makeRequest(url, options);
            displayResponse('alertResponse', 'alertUrl', url + ' [POST]', result);
        }
        
        function toggleAlertFields() {
            const alertType = document.getElementById('alertType').value;
            const targetPriceGroup = document.getElementById('targetPriceGroup');
            const thresholdGroup = document.getElementById('thresholdGroup');
            
            if (alertType === 'price_target') {
                targetPriceGroup.style.display = 'flex';
                thresholdGroup.style.display = 'none';
            } else if (alertType === 'percentage_change') {
                targetPriceGroup.style.display = 'none';
                thresholdGroup.style.display = 'flex';
            } else {
                targetPriceGroup.style.display = 'none';
                thresholdGroup.style.display = 'none';
            }
        }
        
        async function testAllEndpoints() {
            console.log('Testing all endpoints...');
            await testPriceHistory();
            await new Promise(resolve => setTimeout(resolve, 200));
            await testPriceStatistics();
            await new Promise(resolve => setTimeout(resolve, 200));
            await testPriceTrends();
            await new Promise(resolve => setTimeout(resolve, 200));
            await testChartData();
            await new Promise(resolve => setTimeout(resolve, 200));
            await testCreateAlert();
        }
        
        function clearAllResponses() {
            const responseContainers = document.querySelectorAll('.response-container');
            responseContainers.forEach(container => {
                container.style.display = 'none';
            });
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleAlertFields();
        });
    </script>
</body>
</html>