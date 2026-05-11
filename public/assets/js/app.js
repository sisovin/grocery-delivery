(function () {
  const form = document.getElementById('promptForm');
  const output = document.getElementById('promptOutput');

  if (!form || !output) {
    return;
  }

  form.addEventListener('submit', async function (event) {
    event.preventDefault();

    const data = new FormData(form);

    try {
      const response = await fetch('/api/prompt/generate', {
        method: 'POST',
        body: data,
      });

      const payload = await response.json();
      output.textContent = JSON.stringify(payload, null, 2);
    } catch (error) {
      output.textContent = JSON.stringify({ error: 'Request failed' }, null, 2);
    }
  });
})();
