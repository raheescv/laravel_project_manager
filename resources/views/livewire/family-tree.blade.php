<div>
    <div class="container-fluid py-4">
        <div class="row">
            <!-- Add Member Form -->
            <div class="col-md-3" hidden>
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fa fa-user-plus me-2"></i>Add Family Member</h4>
                    </div>
                    <div class="card-body">
                        <form wire:submit.prevent="addMember">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" wire:model="name" required>
                                @error('name')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Gender</label>
                                <select class="form-control" wire:model="gender" required>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                                @error('gender')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Profile Image</label>
                                <input type="file" class="form-control" wire:model="image" accept="image/*">
                                @error('image')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" wire:model="date_of_birth">
                                @error('date_of_birth')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Relationship Type</label>
                                <select class="form-control" wire:model="relation_type">
                                    <option value="">No Relation</option>
                                    <option value="child">Child Of</option>
                                    <option value="spouse">Spouse Of</option>
                                </select>
                            </div>

                            @if ($relation_type === 'child')
                                <div class="mb-3">
                                    <label class="form-label">Parent</label>
                                    <select class="form-control" wire:model="parent_id">
                                        <option value="">Select Parent</option>
                                        @foreach ($potentialParents as $parent)
                                            <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('parent_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            @endif

                            @if ($relation_type === 'spouse')
                                <div class="mb-3">
                                    <label class="form-label">Spouse</label>
                                    <select class="form-control" wire:model="spouse_id">
                                        <option value="">Select Spouse</option>
                                        @foreach ($potentialSpouses as $spouse)
                                            <option value="{{ $spouse->id }}">{{ $spouse->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('spouse_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            @endif

                            <button type="submit" class="btn btn-primary">Add Member</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Family Tree Visualization -->
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary  d-flex justify-content-between align-items-center">
                        <h4 class="mb-0 text-white"><i class="fa fa-sitemap me-2"></i>Family Tree</h4>
                    </div>
                    <div class="card-body p-0">
                        <div id="family-tree" style="height: calc(100vh - 250px); min-height: 600px;"></div>
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                let familyTree = null;
                let loadingTimeout = null;

                // Loading indicator function
                function showLoading() {
                    const treeDiv = document.getElementById("family-tree");
                    treeDiv.innerHTML = `
                        <div class="d-flex justify-content-center align-items-center h-100">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    `;
                }

                document.addEventListener('livewire:initialized', () => {
                    showLoading();
                    initializeTree([]);
                });

                window.addEventListener('updateFamilyTree', (event) => {
                    if (loadingTimeout) {
                        clearTimeout(loadingTimeout);
                    }

                    loadingTimeout = setTimeout(() => {
                        if (familyTree) {
                            familyTree.load(event.detail[0]);
                            familyTree.fit();
                        }
                    }, 300);
                });

                function initializeTree(nodes) {
                    familyTree = new FamilyTree(document.getElementById("family-tree"), {
                        nodeBinding: {
                            field_0: "name",
                            field_1: (sender, node) => {
                                return node.birthDate ? `Born: ${node.birthDate}` : '';
                            }
                        },
                        nodes: nodes,
                        template: "tommy",
                        enableSearch: true,
                        searchFields: ["name", "birthDate"],
                        nodeMenu: {
                            details: {
                                text: "Details",
                                icon: "fa fa-info-circle"
                            },
                            edit: {
                                text: "Edit",
                                icon: "fa fa-edit"
                            },
                            remove: {
                                text: "Remove",
                                icon: "fa fa-trash"
                            }
                        },
                        nodeTreeMenu: true,
                        sticky: true,
                        nodeCircleMenu: {
                            addChild: {
                                text: "Add Child",
                                icon: "fa fa-child"
                            },
                            addParent: {
                                text: "Add Parent",
                                icon: "fa fa-user"
                            },
                            addSpouse: {
                                text: "Add Spouse",
                                icon: "fa fa-heart"
                            }
                        },
                        orientation: window.innerWidth < 768 ? FamilyTree.orientation.bottom : FamilyTree.orientation.top,
                        mouseScrool: FamilyTree.action.zoom,
                        scaleInitial: 0.9,
                        padding: 30,
                        levelSeparation: 100,
                        siblingSeparation: 40,
                        subtreeSeparation: 80,
                        tags: {
                            male: {
                                template: "tommy",
                                nodeMenu: {
                                    addSpouse: {
                                        text: "Add Wife"
                                    }
                                }
                            },
                            female: {
                                template: "tommy",
                                nodeMenu: {
                                    addSpouse: {
                                        text: "Add Husband"
                                    }
                                }
                            },
                            other: {
                                template: "tommy"
                            }
                        }
                    });

                    // Custom node menu event handling
                    familyTree.on('click', function(sender, args) {
                        if (args.node) {
                            return false; // Prevents default click behavior
                        }
                    });

                    // Make the tree responsive
                    window.addEventListener('resize', function() {
                        familyTree.orientation = window.innerWidth < 768 ?
                            FamilyTree.orientation.bottom :
                            FamilyTree.orientation.top;
                        familyTree.draw();
                    });

                    // Add custom node styling
                    const style = document.createElement('style');
                    style.textContent = `
                        [data-tag="male"] rect {
                            fill: #E8F3FF !important;
                        }
                        [data-tag="female"] rect {
                            fill: #FFF0F3 !important;
                        }
                        [data-tag="other"] rect {
                            fill: #F0F0F0 !important;
                        }
                        .bft-edit-form-header, .bft-img-button {
                            background-color: var(--bs-primary) !important;
                        }
                        #family-tree {
                            background: linear-gradient(135deg, #f5f7fa 0%, #ffffff 100%);
                        }
                    `;
                    document.head.appendChild(style);
                }
            </script>
        @endpush
    </div>
</div>
