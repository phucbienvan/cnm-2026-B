from fastapi import APIRouter

router = APIRouter(tags=["Users"])


@router.get("/users")
def get_user() -> dict[str, str]:
    """Retrieve user information."""
    return {
        "name": "phuc bv",
        "age": "28",
        "sex": "boy",
    }


@router.post("/users")
def create_user() -> dict[str, str]:
    """Create a new user."""
    return {
        "new_name": "phuc bv",
        "new_age": "28",
        "new_sex": "boy",
    }


@router.put("/users")
def update_user() -> dict[str, str]:
    """Update an existing user."""
    return {
        "message": "update user successful",
    }


@router.delete("/{id}")
def delete_user(id: str) -> dict[str, str]:
    """Delete a user by ID."""
    return {
        "message": f"deleted user {id}",
    }
