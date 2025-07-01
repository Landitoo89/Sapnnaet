function enviarCorreosCumpleañosDiario() {
  const sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Hoja 1"); // Cambia "Hoja 1"
  const datos = sheet.getDataRange().getValues();
  const hoy = new Date();
  
  // Configurar zona horaria (America/Caracas)
  const zonaHoraria = "America/Caracas";
  const hoyFormateado = Utilities.formatDate(hoy, zonaHoraria, "dd/MM");
  
  for (let i = 1; i < datos.length; i++) {
    try {
      const [nombre, email, fechaDate] = datos[i];
      
      // Validar campos vacíos
      if (!nombre || !email || !fechaDate) {
        console.warn(`Fila ${i + 1}: Datos incompletos. Saltando...`);
        continue;
      }

      // Extraer día y mes del objeto Date
      const fechaCumple = new Date(fechaDate);
      const dia = fechaCumple.getDate(); // Día del mes (1-31)
      const mes = fechaCumple.getMonth() + 1; // Mes (1-12)
      const fechaCumpleFormateada = `${dia.toString().padStart(2, '0')}/${mes.toString().padStart(2, '0')}`;

      // Comparar con la fecha actual (solo día y mes)
      if (fechaCumpleFormateada !== hoyFormateado) {
        console.log(`Fila ${i + 1}: ${nombre} no cumple hoy.`);
        continue;
      }

      // Validar email
      if (!/^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/.test(email)) {
        console.error(`Fila ${i + 1}: Email inválido "${email}"`);
        continue;
      }

      // Enviar correo
      GmailApp.sendEmail(email, "🎉 ¡Feliz cumpleaños!", `Hola ${nombre},\n\n¡Que tengas un día increíble! 🎂`, {
        noReply: true,
      });

      console.log(`✅ Correo enviado a ${nombre} (${email})`);
    } catch (error) {
      console.error(`❌ Error en fila ${i + 1}: ${error.message}`);
    }
  }
}