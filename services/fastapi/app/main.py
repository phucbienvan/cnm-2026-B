
from fastapi import FastAPI

from app.routes.user_routes import router as user_router
from app.routes.health_routes import router as health_router

app = FastAPI(title="CNM FastAPI")

# Đăng ký các route
app.include_router(user_router)
app.include_router(health_router)
