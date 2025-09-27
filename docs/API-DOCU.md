GET /api/expense_resources # Listar gastos
POST /api/expense_resources # Crear gasto
GET /api/expense_resources/{id} # Ver gasto específico
PUT /api/expense_resources/{id} # Actualizar gasto
PATCH /api/expense_resources/{id} # Actualización parcial
DELETE /api/expense_resources/{id} # Eliminar gasto

GET /api/income_resources # Listar ingresos
POST /api/income_resources # Crear ingreso

# ... (mismo patrón para ingresos)

GET /api/budget_resources # Listar presupuestos
POST /api/budget_resources # Crear presupuesto

# ... (mismo patrón para presupuestos)

GET /api/profile_resources # Ver mi perfil
PUT /api/profile_resources # Actualizar mi perfil
PATCH /api/profile_resources # Actualización parcial

GET /api/category_resources # Listar categorías
POST /api/category_resources # Crear categoría
