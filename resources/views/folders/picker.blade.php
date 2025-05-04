<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chọn Thư Mục Google Drive</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        #picker { height: 500px; width: 100%; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Chọn Thư Mục Google Drive</h1>
            <p>Vui lòng chọn thư mục nơi bạn muốn lưu các tài liệu được sao chép.</p>
        </div>
        
        <div id="picker"></div>
    </div>
    
    <!-- Tải Google Picker API -->
    <script type="text/javascript" src="https://apis.google.com/js/api.js"></script>
    <script type="text/javascript" src="https://apis.google.com/js/platform.js"></script>
    
    <script type="text/javascript">
        // Khởi tạo Google Picker khi API đã được tải
        let pickerApiLoaded = false;
        let oauthToken = '{{ $accessToken }}';
        
        // Tải Google Picker API
        function loadPicker() {
            gapi.load('picker', {'callback': onPickerApiLoad});
        }
        
        function onPickerApiLoad() {
            pickerApiLoaded = true;
            createPicker();
        }
        
        // Tạo và hiển thị Picker
        function createPicker() {
            if (pickerApiLoaded && oauthToken) {
                const picker = new google.picker.PickerBuilder()
                    .addView(new google.picker.DocsView(google.picker.ViewId.FOLDERS)
                        .setSelectFolderEnabled(true)
                        .setIncludeFolders(true)
                        .setMode(google.picker.DocsViewMode.LIST))
                    .setOAuthToken(oauthToken)
                    .setCallback(pickerCallback)
                    .setTitle('Chọn thư mục lưu tài liệu')
                    .build();
                picker.setVisible(true);
            }
        }
        
        // Xử lý kết quả chọn từ Picker
        function pickerCallback(data) {
            if (data.action == google.picker.Action.PICKED) {
                const folder = data.docs[0];
                const folderId = folder.id;
                const folderName = folder.name;
                
                // Gửi thông tin thư mục đã chọn về cửa sổ cha
                if (window.opener && !window.opener.closed) {
                    window.opener.setSelectedFolder(folderId, folderName);
                    window.close();
                }
            } else if (data.action == google.picker.Action.CANCEL) {
                window.close();
            }
        }
        
        // Khởi tạo khi trang đã tải xong
        document.addEventListener('DOMContentLoaded', function() {
            loadPicker();
        });
    </script>
</body>
</html>