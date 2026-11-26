from fastapi import APIRouter

router = APIRouter()


@router.get("/users")
def get_user() -> dict[str, str]:
    return {
        "name": "phuc bv",
        "age": "28",
        "sex": "boy",
    }


@router.post("/users")
def create_user() -> dict[str, str]:
    return {
        "new_name": "phuc bv",
        "new_age": "28",
        "new_sex": "boy",
    }


@router.put("/users")
def update_user() -> dict[str, str]:
    return {
        "message": "update user successful",
    }


@router.delete("/{id}")
def delete_user(id) -> dict[str, str]:
    return {
        "message": f"deleted user {id}",
    }
