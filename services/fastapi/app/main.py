from fastapi import FastAPI

from app.routers import router

app = FastAPI(title="CNM FastAPI")

app.include_router(router)
