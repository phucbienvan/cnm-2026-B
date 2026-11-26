from fastapi import APIRouter

from .health import router as health_router
from .users import router as users_router

api_router = APIRouter()
api_router.include_router(health_router)
api_router.include_router(users_router)

__all__ = ["api_router", "health_router", "users_router"]
