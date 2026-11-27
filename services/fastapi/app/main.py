from fastapi import FastAPI
from app.routes.user import router as user_router

app = FastAPI(title="CNM FastAPI")
app.include_router(user_router)

@app.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok"}
