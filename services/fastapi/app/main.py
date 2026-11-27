from fastapi import FastAPI
from app.route.health_route import router as health_router
from app.route.user_route import router as user_router

app = FastAPI(title="CNM FastAPI")
app.include_router(health_router)
app.include_router(user_router)
