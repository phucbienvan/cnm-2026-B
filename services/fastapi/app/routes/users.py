from fastapi import APIRouter

router = APIRouter(prefix="/users", tags=["Users"])


@router.get("")
def get_user() -> dict[str, str]:
    return {
        "name": "phuongn6",
        "age": "22",
        "sex": "girl",
    }


@router.post("")
def create_user() -> dict[str, str]:
    return {
        "new_name": "phuongn6",
        "new_age": "22",
        "new_sex": "boy",
    }


@router.put("")
def update_user() -> dict[str, str]:
    return {
        "message": "update user successful",
    }


@router.delete("/{user_id}")
def delete_user(user_id: int) -> dict[str, str]:
    return {
        "message": f"deleted user {user_id}",
    }
