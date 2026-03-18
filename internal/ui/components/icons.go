package components

import _ "embed"

//go:generate cp ../../../node_modules/bootstrap-icons/icons/star-fill.svg icons/star-fill.svg
//go:generate cp ../../../node_modules/bootstrap-icons/icons/star-half.svg icons/star-half.svg
//go:generate cp ../../../node_modules/bootstrap-icons/icons/star.svg icons/star.svg

//go:embed icons/star-fill.svg
var iconStarFill string

//go:embed icons/star-half.svg
var iconStarHalf string

//go:embed icons/star.svg
var iconStarEmpty string
