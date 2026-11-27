from fastapi import APIRouter

from . import health, users

router = APIRouter()
router.include_router(health.router)
router.include_router(users.router)