from fastapi import FastAPI

from app.routes import health_router, users_router

app = FastAPI(title="CNM FastAPI")

app.include_router(health_router)
app.include_router(users_router)
