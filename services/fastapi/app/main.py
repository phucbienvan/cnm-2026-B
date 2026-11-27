from fastapi import FastAPI

from app.route import health, users

app = FastAPI(title="CNM FastAPI")

app.include_router(health.router)
app.include_router(users.router)
