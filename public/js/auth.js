const API_URL_ME = 'http://localhost:8081/MARS/api/users.php?action=me';

const getCurrentUser = async () => {
  try {
    const response = await fetch(API_URL_ME, {
      method: 'GET',
      headers: {
        'Accept': 'application/json'
      },
      credentials: 'include' // 🔥 importante para mandar la cookie "token"
    });

    const data = await response.json();
    return data;

  } catch (error) {
    console.error('Error obteniendo usuario actual', error);
    return { ok: false, message: 'Error de red' };
  }
};