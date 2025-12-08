document.addEventListener('DOMContentLoaded', function() {
    const formElement = document.getElementById('requestForm');
    
    if (!formElement) {
        console.error('Форма не найдена!');
        return;
    }

    formElement.addEventListener('submit', async (event) => {
        event.preventDefault();
        console.log('Форма отправляется...');

        const formData = new FormData(formElement);

        console.log('Собранные данные:');
        for (let [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }

        const data = Object.fromEntries(formData.entries());
        console.log('Данные для отправки:', data);

        try {
            const response = await fetch('api/submit_request.php', {
                method: 'POST',
                body: formData
            });

            console.log('Статус ответа:', response.status);
            console.log('Статус текст:', response.statusText);

            const responseText = await response.text();
            console.log('Ответ сервера:', responseText);

            if (!responseText.trim()) {
                throw new Error('Сервер вернул пустой ответ');
            }

            let result;
            try {
                result = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('Ошибка парсинга JSON:', jsonError);
                console.error('Сырой ответ:', responseText);
                throw new Error('Сервер вернул некорректный JSON');
            }

            if (result.success) {
                alert(result.message || 'Заявка успешно отправлена!');
                formElement.reset();
                console.log('Заявка успешно отправлена:', result);
            } else {
                alert(result.error || result.message || 'Ошибка при отправке');
                console.error('Ошибка от сервера:', result);
            }

        } catch (error) {
            console.error('Ошибка отправки заявки:', error);
            alert('Ошибка отправки заявки: ' + error.message);
        }
    });

    const fileInput = document.getElementById('files');
    const fileList = document.getElementById('fileList');
    
    if (fileInput && fileList) {
        fileInput.addEventListener('change', function() {
            fileList.innerHTML = '';
            const files = Array.from(this.files);
            
            files.forEach(file => {
                const li = document.createElement('li');
                li.textContent = `${file.name} (${(file.size / 1024).toFixed(2)} KB)`;
                fileList.appendChild(li);
            });
            
            if (files.length > 0) {
                document.getElementById('fileLabel').textContent = `Файлы (${files.length})`;
            }
        });
    }
});