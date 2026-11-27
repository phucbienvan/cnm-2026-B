from fastapi import FastAPI
from app.routes.user import router as user_router
from app.db.base import engine
from sqlalchemy import text


app = FastAPI(title="CNM FastAPI")
app.include_router(user_router)

@app.get("/health")
def health() -> dict[str, str]:
    with engine.connect() as connections:
        connections.execute(text("SELECT 1"))

    return {
        "message" : "database connected"
    }
