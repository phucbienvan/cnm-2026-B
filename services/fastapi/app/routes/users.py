from fastapi import APIRouter

router = APIRouter(prefix="/users", tags=["Users"])


@router.get("")
def get_user() -> dict[str, str]:
    return {
        "name": "le ngoc tan",
        "age": "21",
        "sex": "boy",
    }


@router.post("")
def create_user() -> dict[str, str]:
    return {
        "new_name": "le ngoc tan",
        "new_age": "21",
        "new_sex": "boy",
    }


@router.put("")
def update_user() -> dict[str, str]:
    return {"message": "update user successful"}


@router.delete("/{user_id}")
def delete_user(user_id: int) -> dict[str, str]:
    return {"message": f"deleted user {user_id}"}
